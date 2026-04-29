<?php

declare(strict_types=1);

class AccountBalanceController
{
    public function supplierCashCredits(Request $request): void
    {
        $supplierModel = new Supplier();

        View::make("finance/credits/suppliers", [
            "title" => "Supplier Cash Credit Balance",
            "auth" => auth_user(),
            "suppliers" => $supplierModel->allWithCashCreditBalances(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function customerCashCredits(Request $request): void
    {
        $customerModel = new Customer();

        View::make("finance/credits/customers", [
            "title" => "Customer Cash Credit Balance",
            "auth" => auth_user(),
            "customers" => $customerModel->allWithCashCreditBalances(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function refreshSupplierCashCredits(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/supplier-credit-balances");
        }

        $finance = new Finance();
        $finance->refreshSupplierCashCreditBalances();

        $_SESSION["flash"] = ["type" => "success", "message" => "Supplier cash credit balances refreshed."];
        redirect("/supplier-credit-balances");
    }

    public function refreshCustomerCashCredits(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/customer-credit-balances");
        }

        $finance = new Finance();
        $finance->refreshCustomerCashCreditBalances();

        $_SESSION["flash"] = ["type" => "success", "message" => "Customer cash credit balances refreshed."];
        redirect("/customer-credit-balances");
    }

    public function suppliers(Request $request): void
    {
        $supplierModel = new Supplier();

        View::make("catalog/accounts/suppliers", [
            "title" => "Supplier Account Balance",
            "auth" => auth_user(),
            "suppliers" => $supplierModel->allWithBalances(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function customers(Request $request): void
    {
        $customerModel = new Customer();
        $selectedName = trim((string) $request->input("name", ""));
        $customers = $customerModel->accounts($selectedName, $selectedName === "");

        View::make("catalog/accounts/customers", [
            "title" => "Customer Account Balance",
            "auth" => auth_user(),
            "customers" => $customers,
            "selectedName" => $selectedName,
            "customerOptions" => $customerModel->search("", ""),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }
}
