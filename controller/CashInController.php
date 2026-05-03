<?php

declare(strict_types=1);

class CashInController
{
    public function index(Request $request): void
    {
        $cashInAccountModel = new CashInAccount();
        $accounts = $cashInAccountModel->all();

        View::make("cashier/cashin/index", [
            "title" => "Add Cash In",
            "accounts" => $accounts,
            "flash" => $_SESSION["flash"] ?? null,
        ]);
        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/cashier/cash-in");
        }

        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);
        $shopId = (int) ($auth["shop_id"] ?? 0);

        $accountId = (int) $request->input("account_id", 0);
        $amount = (float) $request->input("amount", 0);
        $reason = (string) $request->input("reason", "");

        if ($accountId <= 0 || $amount <= 0 || trim($reason) === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Please fill all mandatory fields and ensure amount is valid."];
            redirect("/cashier/cash-in");
        }

        $cashierModel = new Cashier();
        $cashierModel->addCashIn($shopId, $userId, $accountId, $amount, trim($reason));

        $_SESSION["flash"] = ["type" => "success", "message" => "Cash In added successfully."];
        redirect("/cashier/cash-in");
    }

    public function storeAccount(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/cashier/cash-in");
        }

        $name = trim((string) $request->input("name", ""));
        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Account name is required."];
            redirect("/cashier/cash-in");
        }

        $cashInAccountModel = new CashInAccount();
        if ($cashInAccountModel->findByName($name)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "This account already exists."];
            redirect("/cashier/cash-in");
        }

        $cashInAccountModel->create($name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Cash In account created."];
        redirect("/cashier/cash-in");
    }

    public function updateAccount(Request $request, array $args): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/cashier/cash-in");
        }

        $id = (int) ($args["id"] ?? 0);
        $name = trim((string) $request->input("name", ""));

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Account name cannot be empty."];
            redirect("/cashier/cash-in");
        }

        $cashInAccountModel = new CashInAccount();
        $cashInAccountModel->update($id, $name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Cash In account updated."];
        redirect("/cashier/cash-in");
    }
}
