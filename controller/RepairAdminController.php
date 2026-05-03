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

    public function faultsUpdate(Request $request, array $args): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/faults");
        }

        $id = (int) ($args["id"] ?? 0);
        $name = trim((string) $request->input("name", ""));
        
        $faultModel = new RepairFault();
        if ($name === "") {
            // Delete action (status 2) if name is empty and they hit 'delete'?
            // Wait, we can just use a separate delete route. But for now update requires a name.
            $_SESSION["flash"] = ["type" => "error", "message" => "Fault name cannot be empty."];
            redirect("/repair/admin/faults");
        }

        $faultModel->update($id, $name);
        $_SESSION["flash"] = ["type" => "success", "message" => "Fault updated."];
        redirect("/repair/admin/faults");
    }

    public function faultsDelete(Request $request, array $args): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/faults");
        }
        
        $id = (int) ($args["id"] ?? 0);
        $faultModel = new RepairFault();
        $faultModel->delete($id);

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

    public function belongsUpdate(Request $request, array $args): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/belongs");
        }

        $id = (int) ($args["id"] ?? 0);
        $name = trim((string) $request->input("name", ""));
        
        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Belong name cannot be empty."];
            redirect("/repair/admin/belongs");
        }

        $belongModel = new RepairBelong();
        $belongModel->update($id, $name);
        
        $_SESSION["flash"] = ["type" => "success", "message" => "Belong updated."];
        redirect("/repair/admin/belongs");
    }

    public function belongsDelete(Request $request, array $args): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/repair/admin/belongs");
        }
        
        $id = (int) ($args["id"] ?? 0);
        $belongModel = new RepairBelong();
        $belongModel->delete($id);

        $_SESSION["flash"] = ["type" => "success", "message" => "Belong deleted."];
        redirect("/repair/admin/belongs");
    }
}
