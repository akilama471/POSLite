<?php

declare(strict_types=1);

class AccountBalanceController
{
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
