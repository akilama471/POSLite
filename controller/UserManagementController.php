<?php

declare(strict_types=1);

class UserManagementController
{
    public function index(Request $request): void
    {
        $userModel = new User();
        $shopModel = new Shop();
        $privilegeModel = new Privilege();

        $shops = [];
        foreach ($shopModel->activeShops() as $shop) {
            $shops[(int) $shop["shopid"]] = $shop["shop_info_name"] ?: $shop["shopname"];
        }

        $privileges = [];
        foreach ($privilegeModel->allOrdered() as $privilege) {
            $privileges[(int) $privilege["privilegeid"]] = $privilege["privilegename"];
        }

        View::make("settings/users/index", [
            "title" => "Manage Users",
            "auth" => auth_user(),
            "users" => $userModel->listManageableUsers(),
            "shops" => $shops,
            "privileges" => $privileges,
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function create(Request $request): void
    {
        $shopModel = new Shop();
        $privilegeModel = new Privilege();

        View::make("settings/users/create", [
            "title" => "Add User",
            "auth" => auth_user(),
            "shops" => $shopModel->activeShops(),
            "privileges" => $privilegeModel->allOrdered(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/settings/users/create");
        }

        $username = trim((string) $request->input("username", ""));
        $displayName = trim((string) $request->input("display_name", ""));
        $shopId = (int) $request->input("shop_id", 0);
        $privilegeId = (int) $request->input("privilege_id", 2);

        if ($username === "" || $displayName === "" || $shopId <= 0) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Username, display name, and shop are required."];
            redirect("/settings/users/create");
        }

        $userModel = new User();

        if ($userModel->existsByUsername($username)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That username is already registered."];
            redirect("/settings/users/create");
        }

        $userModel->createLegacyUser([
            "username" => $username,
            "password" => password_hash("pass123", PASSWORD_BCRYPT),
            "display_name" => $displayName,
            "shop_id" => $shopId,
            "privilege_id" => $privilegeId,
            "addeddate" => date("Y-m-d H:i:s"),
        ]);

        $_SESSION["flash"] = ["type" => "success", "message" => "User created. Default password is pass123."];
        redirect("/settings/users");
    }

    public function updateStatus(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            http_response_code(419);
            exit("Invalid CSRF token.");
        }

        $userId = (int) $id;
        $action = (string) $request->input("action", "");
        $userModel = new User();
        $user = $userModel->findById($userId);

        if ($user === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "User not found."];
            redirect("/settings/users");
        }

        switch ($action) {
            case "reset-password":
                $userModel->resetPassword($userId, password_hash("pass123", PASSWORD_BCRYPT));
                $message = "Password reset to pass123.";
                break;
            case "unlock":
                $userModel->updateStatus($userId, 1);
                $message = "User unlocked.";
                break;
            case "lock":
                $userModel->updateStatus($userId, 2);
                $message = "User locked.";
                break;
            case "delete":
                $userModel->updateStatus($userId, 4);
                $message = "User marked deleted.";
                break;
            default:
                $message = "Unknown action.";
                $_SESSION["flash"] = ["type" => "error", "message" => $message];
                redirect("/settings/users");
        }

        $_SESSION["flash"] = ["type" => "success", "message" => $message];
        redirect("/settings/users");
    }
}
