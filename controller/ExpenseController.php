<?php

declare(strict_types=1);

class ExpenseController
{
    public function index(Request $request): void
    {
        $expenseAccountModel = new ExpenseAccount();
        $accounts = $expenseAccountModel->all();

        View::make("cashier/expenses/index", [
            "title" => "Add Expense",
            "accounts" => $accounts,
            "flash" => $_SESSION["flash"] ?? null,
        ]);
        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/cashier/expenses");
        }

        $auth = auth_user() ?? [];
        $userId = (int) ($auth["user_id"] ?? 0);
        $shopId = (int) ($auth["shop_id"] ?? 0);

        $accountId = (int) $request->input("account_id", 0);
        $amount = (float) $request->input("amount", 0);
        $reason = (string) $request->input("reason", "");

        if ($accountId <= 0 || $amount <= 0 || trim($reason) === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Please fill all mandatory fields and ensure amount is valid."];
            redirect("/cashier/expenses");
        }

        $cashierModel = new Cashier();
        $cashierModel->addExpense($shopId, $userId, $accountId, $amount, trim($reason));

        $_SESSION["flash"] = ["type" => "success", "message" => "Expense added successfully."];
        redirect("/cashier/expenses");
    }

    public function storeAccount(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/cashier/expenses");
        }

        $name = trim((string) $request->input("name", ""));
        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Account name is required."];
            redirect("/cashier/expenses");
        }

        $expenseAccountModel = new ExpenseAccount();
        if ($expenseAccountModel->findByName($name)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "This account already exists."];
            redirect("/cashier/expenses");
        }

        $expenseAccountModel->create($name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Expense account created."];
        redirect("/cashier/expenses");
    }

    public function updateAccount(Request $request, array $args): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/cashier/expenses");
        }

        $id = (int) ($args["id"] ?? 0);
        $name = trim((string) $request->input("name", ""));

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Account name cannot be empty."];
            redirect("/cashier/expenses");
        }

        $expenseAccountModel = new ExpenseAccount();
        $expenseAccountModel->update($id, $name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Expense account updated."];
        redirect("/cashier/expenses");
    }
}
