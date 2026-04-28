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
}
