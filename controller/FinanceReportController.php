<?php

declare(strict_types=1);

class FinanceReportController
{
    public function supplierPayments(Request $request): void
    {
        $supplierModel = new Supplier();
        $companyModel = new Company();
        $financeModel = new Finance();

        $fromDate = $this->normalizeDate((string) $request->input("from", ""));
        $toDate = $this->normalizeDate((string) $request->input("to", ""));
        $supplierId = (int) $request->input("supplier", 0);
        $selectedSupplier = $supplierId > 0 ? $supplierModel->findById($supplierId) : null;
        $rows = $selectedSupplier !== null
            ? $financeModel->supplierPaymentHistory($supplierId, $fromDate, $toDate)
            : [];

        View::make("reports/finance/supplier_payments", [
            "title" => "Supplier Payment Report",
            "auth" => auth_user(),
            "company" => $companyModel->primary(),
            "suppliers" => $supplierModel->allOrdered(),
            "selectedSupplier" => $selectedSupplier,
            "fromDate" => $fromDate,
            "toDate" => $toDate,
            "rows" => $rows,
        ]);
    }

    public function customerPayments(Request $request): void
    {
        $customerModel = new Customer();
        $companyModel = new Company();
        $financeModel = new Finance();

        $fromDate = $this->normalizeDate((string) $request->input("from", ""));
        $toDate = $this->normalizeDate((string) $request->input("to", ""));
        $customerId = (int) $request->input("customer", 0);
        $selectedCustomer = $customerId > 0 ? $customerModel->findById($customerId) : null;
        $rows = $selectedCustomer !== null
            ? $financeModel->customerPaymentHistory($customerId, $fromDate, $toDate)
            : [];

        View::make("reports/finance/customer_payments", [
            "title" => "Customer Payment Report",
            "auth" => auth_user(),
            "company" => $companyModel->primary(),
            "customers" => $customerModel->search("", ""),
            "selectedCustomer" => $selectedCustomer,
            "fromDate" => $fromDate,
            "toDate" => $toDate,
            "rows" => $rows,
        ]);
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);

        if ($value === "") {
            return date("Y-m-d");
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? date("Y-m-d") : date("Y-m-d", $timestamp);
    }
}
