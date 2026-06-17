<?php

declare(strict_types=1);

class RepairAdminController
{
    public function faultsIndex(Request $request): void
    {
        $faultModel = new RepairFault();
        $faults = $faultModel->all();

        View::make("repair/admin/faults", [
            "title" => "Manage Common Faults",
            "faults" => $faults,
            "flash" => $_SESSION["flash"] ?? null,
        ]);
        unset($_SESSION["flash"]);
    }

    public function faultsStore(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/faults");
        }

        $name = trim((string) $request->input("name", ""));
        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Fault name is required."];
            redirect("/repair/admin/faults");
        }

        $faultModel = new RepairFault();
        $faultModel->create($name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Fault added."];
        redirect("/repair/admin/faults");
    }

    public function faultsUpdate(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/faults");
        }

        $recordId = (int) $id;
        $name = trim((string) $request->input("name", ""));

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Fault name cannot be empty."];
            redirect("/repair/admin/faults");
        }

        $faultModel = new RepairFault();
        $faultModel->update($recordId, $name);
        $_SESSION["flash"] = ["type" => "success", "message" => "Fault updated."];
        redirect("/repair/admin/faults");
    }

    public function faultsDelete(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/faults");
        }

        $faultModel = new RepairFault();
        $faultModel->delete((int) $id);

        $_SESSION["flash"] = ["type" => "success", "message" => "Fault deleted."];
        redirect("/repair/admin/faults");
    }

    // --- Belongs ---

    public function belongsIndex(Request $request): void
    {
        $belongModel = new RepairBelong();
        $belongs = $belongModel->allActive();

        View::make("repair/admin/belongs", [
            "title" => "Manage Customer Belongs",
            "belongs" => $belongs,
            "flash" => $_SESSION["flash"] ?? null,
        ]);
        unset($_SESSION["flash"]);
    }

    public function belongsStore(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/belongs");
        }

        $name = trim((string) $request->input("name", ""));
        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Belong name is required."];
            redirect("/repair/admin/belongs");
        }

        $belongModel = new RepairBelong();
        $belongModel->create($name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Belong added."];
        redirect("/repair/admin/belongs");
    }

    public function belongsUpdate(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/belongs");
        }

        $recordId = (int) $id;
        $name = trim((string) $request->input("name", ""));

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Belong name cannot be empty."];
            redirect("/repair/admin/belongs");
        }

        $belongModel = new RepairBelong();
        $belongModel->update($recordId, $name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Belong updated."];
        redirect("/repair/admin/belongs");
    }

    public function belongsDelete(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/belongs");
        }

        $belongModel = new RepairBelong();
        $belongModel->delete((int) $id);

        $_SESSION["flash"] = ["type" => "success", "message" => "Belong deleted."];
        redirect("/repair/admin/belongs");
    }
}
