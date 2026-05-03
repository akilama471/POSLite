<?php

declare(strict_types=1);

class RepairReleaseController
{
    public function index(Request $request): void
    {
        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);

        $jobModel = new RepairJob();
        // Load jobs in status 3 (Finished technically)
        $jobs = $jobModel->getJobsByStatus([3], $shopId);

        View::make("repair/release/index", [
            "title" => "Repair Job Release",
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
        
        $partCost = 0.0;
        foreach ($logs as $log) {
            if ($log['op_type'] === "2") {
                $partCost += (float) $log['item_sell_price'];
            }
        }

        echo json_encode([
            "partCost" => $partCost,
            "advPayment" => (float) $job['job_payment_adv'],
            "warranty" => (int) $job['rp_for_warranty'] === 1
        ]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/release");
        }

        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);

        $jobId = trim((string) $request->input("job_id", ""));
        $partCost = (float) $request->input("part_cost", 0);
        $repairCost = (float) $request->input("repair_cost", 0);
        $total = (float) $request->input("total", 0);
        $warrantySpan = trim((string) $request->input("warranty_span", ""));
        $warrantyType = trim((string) $request->input("warranty_type", ""));
        $printWarranty = (int) $request->input("print_warranty", 0);

        if ($jobId === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Job ID is required."];
            redirect("/repair/release");
        }

        $jobModel = new RepairJob();
        $jobModel->makeBill($jobId, $partCost, $repairCost, $total, $warrantySpan, $warrantyType, $printWarranty);

        $logModel = new RepairLog();
        $logModel->db = $jobModel->db;
        $logModel->addLog($jobId, "1", "Bill finalized for repair job.", "NA", 0, 0, $userId);

        $_SESSION["flash"] = ["type" => "success", "message" => "Bill finalized for Job $jobId."];
        redirect("/repair/release");
    }
}
