<?php

declare(strict_types=1);

class FinanceReportController
{
    public function grnList(Request $request): void
    {
        $auth = auth_user() ?? [];
        $authShopId = (int) ($auth["shop_id"] ?? 0);
        $shopModel = new Shop();
        $companyModel = new Company();
        $grnModel = new Grn();

        $fromDate = $this->normalizeDate((string) $request->input("from", ""));
        $toDate = $this->normalizeDate((string) $request->input("to", ""));
        $shopId = $authShopId > 0 ? $authShopId : (int) $request->input("shop", -1);
        $rows = $grnModel->reportList($fromDate, $toDate, $shopId, $authShopId);

        View::make("reports/grns/list", [
            "title" => "GRN List Report",
            "auth" => $auth,
            "company" => $companyModel->primary(),
            "shops" => $authShopId > 0
                ? array_filter($shopModel->allOrdered(), static fn (array $shop): bool => (int) ($shop["shopid"] ?? 0) === $authShopId)
                : $shopModel->allOrdered(),
            "selectedShopId" => $shopId,
            "fromDate" => $fromDate,
            "toDate" => $toDate,
            "rows" => $rows,
        ]);
    }

    public function grnDetail(Request $request, string $grnRefNo): void
    {
        $auth = auth_user() ?? [];
        $authShopId = (int) ($auth["shop_id"] ?? 0);
        $companyModel = new Company();
        $grnModel = new Grn();
        $detail = $grnModel->reportDetail($grnRefNo, $authShopId);

        if ($detail === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "GRN detail was not found."];
            redirect("/reports/grns");
        }

        View::make("reports/grns/detail", [
            "title" => "GRN Detail Report",
            "auth" => $auth,
            "company" => $companyModel->primary(),
            "detail" => $detail,
        ]);
    }

    public function grnSupplierSummary(Request $request): void
    {
        $companyModel = new Company();
        $supplierModel = new Supplier();
        $grnModel = new Grn();

        $fromDate = $this->normalizeDate((string) $request->input("from", ""));
        $toDate = $this->normalizeDate((string) $request->input("to", ""));
        $supplierId = (int) $request->input("supplier", -1);
        $rows = $grnModel->reportSupplierSummary($fromDate, $toDate, $supplierId);
        $selectedSupplier = $supplierId > 0 ? $supplierModel->findById($supplierId) : null;

        View::make("reports/grns/summary", [
            "title" => "GRN Report",
            "auth" => auth_user(),
            "company" => $companyModel->primary(),
            "suppliers" => $supplierModel->allOrdered(),
            "selectedSupplier" => $selectedSupplier,
            "fromDate" => $fromDate,
            "toDate" => $toDate,
            "rows" => $rows,
        ]);
    }

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
