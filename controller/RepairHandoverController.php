<?php

declare(strict_types=1);

class RepairHandoverController
{
    public function index(Request $request): void
    {
        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);

        $jobModel = new RepairJob();
        // Load jobs in status 4 (Ready to Handover)
        $jobs = $jobModel->getJobsByStatus([4], $shopId);

        View::make("repair/handover/index", [
            "title" => "Repair Job Handover",
            "jobs" => $jobs,
            "flash" => $_SESSION["flash"] ?? null,
        ]);
        unset($_SESSION["flash"]);
    }

    public function loadJobData(Request $request): void
    {
        $jobId = trim((string) $request->input("job_id", ""));
        if ($jobId === "") {
            echo json_encode(["error" => "No Job ID provided."]);
            return;
        }

        $jobModel = new RepairJob();
        $job = $jobModel->findByJobNumber($jobId);

        if (!$job) {
            echo json_encode(["error" => "Job not found."]);
            return;
        }

        $logModel = new RepairLog();
        $logs = $logModel->getLogs($jobId);

        $html = "";
        foreach ($logs as $log) {
            $html .= "<div style='font-size: 14px; margin-bottom: 4px;'>" . htmlspecialchars($log['record_time'] . " - " . $log['a_item_name']) . "</div>";
        }

        echo json_encode([
            "totalCost" => (float) $job['job_payment_total'],
            "advPayment" => (float) $job['job_payment_adv'],
            "balanceToPay" => max(0, (float) $job['job_payment_total'] - (float) $job['job_payment_adv']),
            "logsHtml" => $html
        ]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/handover");
        }

        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);
        $shopId = (int) ($auth["shop_id"] ?? 0);

        $jobId = trim((string) $request->input("job_id", ""));
        $cash = (float) $request->input("cash_pay", 0);
        $card = (float) $request->input("card_pay", 0);
        $cardNumber = trim((string) $request->input("card_number", ""));
        $balance = (float) $request->input("balance", 0);
        
        $totalGiven = $cash + $card;

        if ($jobId === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Job ID is required."];
            redirect("/repair/handover");
        }

        $jobModel = new RepairJob();
        
        try {
            $jobModel->db->beginTransaction();

            $jobModel->handover($jobId, $cash, $card, $cardNumber, $balance, $totalGiven);

            $cashierModel = new Cashier();
            $cashierModel->db = $jobModel->db;

            if ($cash > 0) {
                $cashBookAmt = $cash - $balance;
                $remark = "Payment for repair bill " . $jobId;
                $stmt = $cashierModel->db->prepare(
                    "INSERT INTO cash_book (op_date, shop, user, pay_type, remark, open_balance, cash_in, cash_out, close_balance)
                     VALUES (:now, :shop, :user, 1, :remark, 0, :cash_in, 0, 0)"
                );
                $stmt->execute([
                    "now" => date('Y-m-d H:i:s'),
                    "shop" => $shopId,
                    "user" => $userId,
                    "remark" => $remark,
                    "cash_in" => $cashBookAmt,
                ]);
            }

            if ($card > 0) {
                $remark = "Payment for repair bill " . $jobId . " (Card Number:" . $cardNumber . ")";
                $stmt = $cashierModel->db->prepare(
                    "INSERT INTO cash_book (op_date, shop, user, pay_type, remark, open_balance, cash_in, cash_out, close_balance)
                     VALUES (:now, :shop, :user, 2, :remark, 0, :cash_in, 0, 0)"
                );
                $stmt->execute([
                    "now" => date('Y-m-d H:i:s'),
                    "shop" => $shopId,
                    "user" => $userId,
                    "remark" => $remark,
                    "cash_in" => $card,
                ]);
            }

            $logModel = new RepairLog();
            $logModel->db = $jobModel->db;
            $logModel->addLog($jobId, "1", "Bill Settled by customer", "NA", 0, 0, $userId);

            $jobModel->db->commit();
            
            $_SESSION["flash"] = ["type" => "success", "message" => "Job $jobId handed over successfully."];
            redirect("/repair/handover");

        } catch (Throwable $e) {
            $jobModel->db->rollBack();
            $_SESSION["flash"] = ["type" => "error", "message" => "Failed to handover job: " . $e->getMessage()];
            redirect("/repair/handover");
        }
    }
}
