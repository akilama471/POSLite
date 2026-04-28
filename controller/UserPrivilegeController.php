<?php

declare(strict_types=1);

class UserPrivilegeController
{
    public function index(Request $request): void
    {
        $userModel = new User();
        $privilegeModel = new Privilege();
        $privileges = $privilegeModel->allOrdered();

        View::make("settings/user-privileges/index", [
            "title" => "User Function Mapping",
            "auth" => auth_user(),
            "users" => $userModel->listManageableUsers(),
            "privileges" => $privileges,
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/settings/user-privileges");
        }

        $privilegeId = (int) $request->input("privilege_id", 0);
        if ($privilegeId <= 0) {
            $_SESSION["flash"] = ["type" => "error", "message" => "A privilege group must be selected."];
            redirect("/settings/user-privileges");
        }

        $userModel = new User();
        $userModel->updatePrivilege((int) $id, $privilegeId);

        $_SESSION["flash"] = ["type" => "success", "message" => "User privilege mapping updated."];
        redirect("/settings/user-privileges");
    }
}
