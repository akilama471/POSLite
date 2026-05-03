<?php

declare(strict_types=1);

class RepairProcessController
{
    public function index(Request $request): void
    {
        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);

        $jobModel = new RepairJob();
        // Load jobs in status 1 (Created) or 2 (Processing)
        $jobs = $jobModel->getJobsByStatus([1, 2], $shopId);

        View::make("repair/process/index", [
            "title" => "Repair Job Process",
            "jobs" => $jobs,
            "flash" => $_SESSION["flash"] ?? null,
        ]);
        unset($_SESSION["flash"]);
    }

    public function loadJob(Request $request): void
    {
        $jobId = trim((string) $request->input("job_id", ""));
        if ($jobId === "") {
            echo json_encode(["error" => "No Job ID provided."]);
            return;
        }

        $logModel = new RepairLog();
        $logs = $logModel->getLogs($jobId);

        $html = "";
        foreach ($logs as $log) {
            $html .= "<option value='" . $log['recordid'] . "'>" . htmlspecialchars($log['record_time'] . " - " . $log['a_item_name']) . "</option>";
        }

        echo $html;
    }

    public function addPart(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/process");
        }

        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);
        $shopId = (int) ($auth["shop_id"] ?? 0);

        $jobId = trim((string) $request->input("job_id", ""));
        $barcode = trim((string) $request->input("barcode", ""));

        if ($jobId === "" || $barcode === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Job ID and Barcode are required."];
            redirect("/repair/process");
        }

        $jobModel = new RepairJob();
        
        // Update job status to 2 if it's 1
        $job = $jobModel->findByJobNumber($jobId);
        if ($job && (int)$job['job_status'] === 1) {
            $stmt = $jobModel->db->prepare("UPDATE repair_job_list SET job_status = 2 WHERE job_number = :job");
            $stmt->execute(["job" => $jobId]);
        }

        try {
            $jobModel->addPart($jobId, $barcode, $userId, $shopId);
            $_SESSION["flash"] = ["type" => "success", "message" => "Part added successfully."];
        } catch (Throwable $e) {
            $_SESSION["flash"] = ["type" => "error", "message" => $e->getMessage()];
        }

        redirect("/repair/process");
    }

    public function finishTechnical(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/process");
        }

        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);
        $jobId = trim((string) $request->input("job_id", ""));

        if ($jobId === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Job ID is required."];
            redirect("/repair/process");
        }

        $jobModel = new RepairJob();
        $jobModel->finishTechnical($jobId);

        $logModel = new RepairLog();
        $logModel->db = $jobModel->db;
        $logModel->addLog($jobId, "1", "Repair Job Technically Finished", "NA", 0, 0, $userId);

        $_SESSION["flash"] = ["type" => "success", "message" => "Job $jobId finished successfully."];
        redirect("/repair/process");
    }
}
