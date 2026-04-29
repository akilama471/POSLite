<?php

declare(strict_types=1);

class CashierController
{
    public function index(Request $request): void
    {
        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);
        $cashierModel = new Cashier();
        $context = $cashierModel->dutyContext($userId);

        View::make("cashier/index", [
            "title" => "Cashier Duty",
            "auth" => $auth,
            "context" => $context,
            "canDutyOn" => can("p_59"),
            "canDutyOff" => can("p_58"),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function start(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/cashier");
        }

        if (!can("p_59")) {
            http_response_code(403);
            View::make("errors/403", [
                "title" => "Forbidden",
                "message" => "You do not have permission to open cashier duty.",
            ]);
            return;
        }

        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $cashOpen = (float) $request->input("cash_open_balance", 0);
        $cardOpen = (float) $request->input("card_open_balance", 0);

        if ($cashOpen < 0 || $cardOpen < 0) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Opening balances cannot be negative."];
            redirect("/cashier");
        }

        $cashierModel = new Cashier();
        $context = $cashierModel->dutyContext($userId);

        if ($context["slot"] === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "You are not assigned to a cashier slot."];
            redirect("/cashier");
        }

        if (!$context["canStart"]) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Cashier duty is already active or not available for sign-in."];
            redirect("/cashier");
        }

        $cashierModel->startDuty((int) $context["slot"]["recordid"], $shopId, $userId, $cashOpen, $cardOpen);
        sync_cashier_session(true, (int) $context["slot"]["recordid"]);

        $_SESSION["flash"] = ["type" => "success", "message" => "Cashier duty opened successfully."];
        redirect("/cashier");
    }

    public function close(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/cashier");
        }

        if (!can("p_58")) {
            http_response_code(403);
            View::make("errors/403", [
                "title" => "Forbidden",
                "message" => "You do not have permission to close cashier duty.",
            ]);
            return;
        }

        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);
        $cashClose = (float) $request->input("cash_close_balance", 0);
        $cardClose = (float) $request->input("card_close_balance", 0);

        if ($cashClose < 0 || $cardClose < 0) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Closing balances cannot be negative."];
            redirect("/cashier");
        }

        $cashierModel = new Cashier();
        $context = $cashierModel->dutyContext($userId);

        if ($context["slot"] === null || !$context["isActive"]) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Cashier duty is not active for this user."];
            redirect("/cashier");
        }

        $cashierModel->closeDuty((int) $context["slot"]["recordid"], $userId, $cashClose, $cardClose);
        sync_cashier_session(false, null);

        $_SESSION["flash"] = ["type" => "success", "message" => "Cashier duty closed successfully."];
        redirect("/cashier");
    }
}
