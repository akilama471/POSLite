<?php

declare(strict_types=1);

class RepairJobController
{
    public function create(Request $request): void
    {
        $faultModel = new RepairFault();
        $faults = $faultModel->all();

        $belongModel = new RepairBelong();
        $belongs = $belongModel->allActive();

        View::make("repair/jobs/create", [
            "title" => "New Repair Job",
            "faults" => $faults,
            "belongs" => $belongs,
            "flash" => $_SESSION["flash"] ?? null,
        ]);
        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/jobs/new");
        }

        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);
        $shopId = (int) ($auth["shop_id"] ?? 0);

        $cusName = trim((string) $request->input("cus_name", ""));
        $cusImei = trim((string) $request->input("cus_imei", ""));
        $cusAddr = trim((string) $request->input("cus_addr", ""));
        $cusCont = trim((string) $request->input("cus_contact", ""));
        $cusModel = trim((string) $request->input("cus_model", ""));
        $faultDesc = trim((string) $request->input("fault", ""));
        $advPayment = (float) $request->input("adv_payment", 0);
        $warrantyDevice = (int) $request->input("warranty_device", 0);
        
        if ($cusName === "" || $cusCont === "" || $faultDesc === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Customer Name, Contact, and Fault are required."];
            redirect("/repair/jobs/new");
        }

        $jobModel = new RepairJob();
        $jobIdData = $jobModel->generateJobNumber($shopId, $userId);
        $jobNumber = $jobIdData["job_number"];

        $jobData = [
            "job_number" => $jobNumber,
            "job_month" => $jobIdData["job_month"],
            "job_sequ" => $jobIdData["job_sequ"],
            "job_inshop" => $shopId,
            "job_operator" => $userId,
            "job_cus_name" => $cusName,
            "job_cus_imei" => $cusImei,
            "job_cus_model" => $cusModel,
            "job_cus_addr" => $cusAddr,
            "job_cus_contac" => $cusCont,
            "job_fault" => $faultDesc,
            "job_payment_adv" => $advPayment,
            "rp_for_warranty" => $warrantyDevice,
        ];

        try {
            $jobModel->db->beginTransaction();

            $jobModel->createJob($jobData);

            $logModel = new RepairLog();
            $logModel->db = $jobModel->db;
            $logModel->addLog($jobNumber, "1", "Created Repair Job", "NA", 0, 0, $userId);

            if ($advPayment > 0) {
                // Insert to cash_book (Requires Cashier model or direct insert)
                $remark = "Advanced payment for repair bill " . $jobNumber;
                $cashierModel = new Cashier();
                $cashierModel->db = $jobModel->db;
                $cashBookStmt = $cashierModel->db->prepare(
                    "INSERT INTO cash_book (op_date, shop, user, pay_type, remark, open_balance, cash_in, cash_out, close_balance)
                     VALUES (:now, :shop, :user, 1, :remark, 0, :cash_in, 0, 0)"
                );
                $cashBookStmt->execute([
                    "now" => date('Y-m-d H:i:s'),
                    "shop" => $shopId,
                    "user" => $userId,
                    "remark" => $remark,
                    "cash_in" => $advPayment,
                ]);
            }

            // Map belongs
            $belongsInput = $request->input("belongs", []);
            if (is_array($belongsInput) && count($belongsInput) > 0) {
                $belongModel = new RepairBelong();
                $belongModel->db = $jobModel->db;
                $belongModel->mapBelongs($jobNumber, $belongsInput);
            }

            $jobModel->db->commit();
            
            $_SESSION["flash"] = ["type" => "success", "message" => "Job $jobNumber created successfully."];
            redirect("/repair/jobs/new");

        } catch (Throwable $e) {
            if ($jobModel->db->inTransaction()) {
                $jobModel->db->rollBack();
            }
            $_SESSION["flash"] = ["type" => "error", "message" => "Failed to create job: " . $e->getMessage()];
            redirect("/repair/jobs/new");
        }
    }
}
