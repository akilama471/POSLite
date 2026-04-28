<?php

declare(strict_types=1);

class AuthController
{
    public function showLoginForm(Request $request): void
    {
        View::make("auth/login", [
            "title" => "Login",
            "error" => $_SESSION["flash_error"] ?? null,
        ]);

        unset($_SESSION["flash_error"]);
    }

    public function login(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash_error"] = "Your session expired. Please try again.";
            redirect("/login");
        }

        $username = trim((string) $request->input("u-name", ""));
        $password = (string) $request->input("u-pass", "");

        if ($username === "" || $password === "") {
            $_SESSION["flash_error"] = "Username and password are required.";
            redirect("/login");
        }

        $userModel = new User();
        $permissionModel = new Permission();
        $shopModel = new Shop();

        $user = $userModel->findActiveByUsername($username);

        if ($user === null || sha1($password) !== ($user["murapadaya"] ?? "")) {
            $_SESSION["flash_error"] = "Invalid username or password.";
            redirect("/login");
        }

        session_regenerate_id(true);

        $permissions = $permissionModel->forUser((int) $user["myid"]);
        $shop = $shopModel->findByShopId((int) ($user["shop_id"] ?? 0));

        $_SESSION["auth"] = [
            "user_id" => (int) $user["myid"],
            "username" => $user["ankaya"] ?? "",
            "display_name" => $user["visibledata"] ?? $user["ankaya"] ?? "User",
            "shop_id" => (int) ($user["shop_id"] ?? 0),
            "shop_name" => $shop["shop_info_name"] ?? $shop["shopname"] ?? "All Shops",
            "shop_phone" => $shop["shop_tel_1"] ?? "",
            "privilege_id" => (int) ($user["privilageid"] ?? 0),
            "permissions" => $permissions,
        ];

        redirect("/dashboard");
    }

    public function logout(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            http_response_code(419);
            exit("Invalid CSRF token.");
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                "",
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"],
            );
        }

        session_destroy();
        redirect("/login");
    }
}
