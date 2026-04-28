<?php

declare(strict_types=1);

class UserProfileController
{
    public function edit(Request $request): void
    {
        $userModel = new User();
        $shopModel = new Shop();
        $auth = auth_user();
        $user = $userModel->findById((int) $auth["user_id"]);
        $shop = $shopModel->findByShopId((int) $auth["shop_id"]);

        View::make("settings/profile/edit", [
            "title" => "My Account",
            "auth" => $auth,
            "user" => $user,
            "shop" => $shop,
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function updateDetails(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/settings/profile");
        }

        $auth = auth_user();
        $userModel = new User();
        $displayName = trim((string) $request->input("display_name", ""));

        if ($displayName === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Display name is required."];
            redirect("/settings/profile");
        }

        $userModel->updateProfile((int) $auth["user_id"], [
            "display_name" => $displayName,
            "email" => trim((string) $request->input("email", "")),
            "mobile" => trim((string) $request->input("mobile", "")),
        ]);

        $_SESSION["auth"]["display_name"] = $displayName;
        $_SESSION["flash"] = ["type" => "success", "message" => "Your profile details were updated."];
        redirect("/settings/profile");
    }

    public function updatePassword(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/settings/profile");
        }

        $auth = auth_user();
        $userModel = new User();
        $user = $userModel->findById((int) $auth["user_id"]);

        $current = (string) $request->input("current_password", "");
        $new = (string) $request->input("new_password", "");
        $confirm = (string) $request->input("confirm_password", "");

        if (sha1($current) !== ($user["murapadaya"] ?? "")) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Current password does not match."];
            redirect("/settings/profile");
        }

        if ($new === "" || $new !== $confirm) {
            $_SESSION["flash"] = ["type" => "error", "message" => "New password confirmation failed."];
            redirect("/settings/profile");
        }

        $userModel->updatePassword((int) $auth["user_id"], sha1($new));
        $_SESSION["flash"] = ["type" => "success", "message" => "Password updated successfully."];
        redirect("/settings/profile");
    }
}
