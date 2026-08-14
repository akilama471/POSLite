<?php

declare(strict_types=1);

class AuthController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 60;

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

        // ── Rate limiting ─────────────────────────────────────────────────────
        $attempts  = (int) ($_SESSION["_login_attempts"] ?? 0);
        $lockedAt  = isset($_SESSION["_login_locked_at"]) ? (int) $_SESSION["_login_locked_at"] : null;

        if ($lockedAt !== null) {
            $remaining = self::LOCKOUT_SECONDS - (time() - $lockedAt);
            if ($remaining > 0) {
                $_SESSION["flash_error"] = "Too many failed attempts. Please wait {$remaining} second(s) before trying again.";
                redirect("/login");
            }
            // Lockout expired — reset counters
            unset($_SESSION["_login_attempts"], $_SESSION["_login_locked_at"]);
            $attempts = 0;
        }
        // ─────────────────────────────────────────────────────────────────────

        $username = trim((string) $request->input("u-name", ""));
        $password = (string) $request->input("u-pass", "");

        if ($username === "" || $password === "") {
            $_SESSION["flash_error"] = "Username and password are required.";
            redirect("/login");
        }

        $userModel       = new User();
        $permissionModel = new Permission();
        $shopModel       = new Shop();

        $user = $userModel->findActiveByUsername($username);

        if ($user === null || !$userModel->verifyPassword($user, $password)) {
            // Increment failure counter
            $attempts++;
            $_SESSION["_login_attempts"] = $attempts;

            if ($attempts >= self::MAX_ATTEMPTS) {
                $_SESSION["_login_locked_at"] = time();
                unset($_SESSION["_login_attempts"]);
                $_SESSION["flash_error"] = "Too many failed attempts. Please wait " . self::LOCKOUT_SECONDS . " second(s) before trying again.";
            } else {
                $remaining = self::MAX_ATTEMPTS - $attempts;
                $_SESSION["flash_error"] = "Invalid username or password. " . $remaining . " attempt(s) remaining.";
            }

            redirect("/login");
        }

        // Successful login — clear rate limit counters
        unset($_SESSION["_login_attempts"], $_SESSION["_login_locked_at"]);

        session_regenerate_id(true);

        $permissions = $permissionModel->forUser((int) ($user["privilageid"] ?? 0));
        $shop        = $shopModel->findByShopId((int) ($user["shop_id"] ?? 0));

        $_SESSION["auth"] = [
            "user_id"         => (int) $user["myid"],
            "username"        => $user["ankaya"] ?? "",
            "display_name"    => $user["visibledata"] ?? $user["ankaya"] ?? "User",
            "shop_id"         => (int) ($user["shop_id"] ?? 0),
            "shop_name"       => $shop["shop_info_name"] ?? $shop["shopname"] ?? "All Shops",
            "shop_phone"      => $shop["shop_tel_1"] ?? "",
            "privilege_id"    => (int) ($user["privilageid"] ?? 0),
            "user_role"       => $user["user_role"] ?? "",
            "permissions"     => $permissions,
            "cashier_on"      => false,
            "cashier_slot_id" => null,
        ];

        sync_cashier_session(false, null);
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
