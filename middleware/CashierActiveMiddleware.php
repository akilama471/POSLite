<?php

declare(strict_types=1);

class CashierActiveMiddleware implements Middleware
{
    public function handle($request, $next)
    {
        $auth = auth_user();

        if (!is_array($auth) || empty($auth["user_id"])) {
            redirect("/login");
        }

        $cashier = new Cashier();

        if (!$cashier->isActiveForUser((int) $auth["user_id"])) {
            $_SESSION["flash"] = [
                "type" => "error",
                "message" => "You have not signed in to cashier duty. Please open cashier duty before this operation.",
            ];
            redirect("/cashier");
        }

        return $next();
    }
}
