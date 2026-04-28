<?php

declare(strict_types=1);

class PrivilegeController
{
    public function index(Request $request): void
    {
        $privilegeModel = new Privilege();

        View::make("settings/privileges/index", [
            "title" => "Function Permission",
            "auth" => auth_user(),
            "privileges" => $privilegeModel->allOrdered(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/settings/privileges");
        }

        $name = trim((string) $request->input("name", ""));
        $privilegeModel = new Privilege();

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Permission group name is required."];
            redirect("/settings/privileges");
        }

        if ($privilegeModel->existsByName($name)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That permission group already exists."];
            redirect("/settings/privileges");
        }

        $privilegeModel->createByName($name);
        $_SESSION["flash"] = ["type" => "success", "message" => "Permission group created."];
        redirect("/settings/privileges");
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/settings/privileges");
        }

        $name = trim((string) $request->input("name", ""));
        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Permission group name is required."];
            redirect("/settings/privileges");
        }

        $privilegeModel = new Privilege();
        $privilegeModel->updateName((int) $id, $name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Permission group renamed."];
        redirect("/settings/privileges");
    }

    public function editFunctions(Request $request, string $id): void
    {
        $this->renderMatrix((int) $id, "functions");
    }

    public function updateFunctions(Request $request, string $id): void
    {
        $this->saveMatrix($request, (int) $id, "functions");
    }

    public function editReports(Request $request, string $id): void
    {
        $this->renderMatrix((int) $id, "reports");
    }

    public function updateReports(Request $request, string $id): void
    {
        $this->saveMatrix($request, (int) $id, "reports");
    }

    private function renderMatrix(int $id, string $type): void
    {
        $privilegeModel = new Privilege();
        $permissionModel = new Permission();
        $privilege = $privilegeModel->findByPrivilegeId($id);

        if ($privilege === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Permission group not found."];
            redirect("/settings/privileges");
        }

        $catalog = $type === "functions"
            ? PermissionCatalog::functionPermissions()
            : PermissionCatalog::reportPermissions();

        View::make("settings/privileges/matrix", [
            "title" => $type === "functions" ? "Edit Function Permission" : "Edit Report Permission",
            "auth" => auth_user(),
            "type" => $type,
            "privilege" => $privilege,
            "catalog" => $catalog,
            "values" => $permissionModel->forMap($id),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    private function saveMatrix(Request $request, int $id, string $type): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect($this->matrixPath($id, $type));
        }

        $catalog = $type === "functions"
            ? PermissionCatalog::functionPermissions()
            : PermissionCatalog::reportPermissions();

        $values = [];
        foreach ($catalog as $key => $label) {
            $values[$key] = $request->input($key) === "1";
        }

        $permissionModel = new Permission();
        $permissionModel->syncMap($id, $values);

        $_SESSION["flash"] = ["type" => "success", "message" => "Permission matrix updated."];
        redirect($this->matrixPath($id, $type));
    }

    private function matrixPath(int $id, string $type): string
    {
        return $type === "functions"
            ? "/settings/privileges/{$id}/functions"
            : "/settings/privileges/{$id}/reports";
    }
}
