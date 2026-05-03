<?php

declare(strict_types=1);

class ReportController
{
    public function index(Request $request): void
    {
        View::make("reports/index", [
            "title" => "System Reports",
        ]);
    }

    public function shopSale(Request $request): void
    {
        // Load shops for the filter
        $shopModel = new Shop();
        $shops = $shopModel->all();

        View::make("reports/sales/shop", [
            "title" => "Shop Sale Report",
            "shops" => $shops,
        ]);
    }

    public function categorySale(Request $request): void
    {
        // Load shops and categories for the filter
        $shopModel = new Shop();
        $shops = $shopModel->all();

        $catModel = new Category();
        $categories = $catModel->all();

        View::make("reports/sales/category", [
            "title" => "Category Wise Sale Report",
            "shops" => $shops,
            "categories" => $categories,
        ]);
    }

    public function cashierTransactions(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();

        View::make("reports/cashier/transactions", [
            "title" => "Shop Transaction List",
            "shops" => $shops,
        ]);
    }

    public function cashierOpenClose(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();

        View::make("reports/cashier/openclose", [
            "title" => "Shop Open Close Balance",
            "shops" => $shops,
        ]);
    }

    public function cashierExpenses(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();

        View::make("reports/cashier/expenses", [
            "title" => "Shop Expenses",
            "shops" => $shops,
        ]);
    }

    public function cashierProfit(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();

        View::make("reports/cashier/profit", [
            "title" => "Shop Sale Profit",
            "shops" => $shops,
        ]);
    }

    public function productCategories(Request $request): void
    {
        View::make("reports/product/categories", [
            "title" => "Product Category List",
        ]);
    }

    public function productList(Request $request): void
    {
        $catModel = new Category();
        $categories = $catModel->all();

        View::make("reports/product/items", [
            "title" => "Product List",
            "categories" => $categories,
        ]);
    }

    public function productStock(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();

        $catModel = new Category();
        $categories = $catModel->all();

        View::make("reports/product/stock", [
            "title" => "Comprehensive Stock Report",
            "shops" => $shops,
            "categories" => $categories,
        ]);
    }

    // ── Supplier ──────────────────────────────────────────────

    public function supplierMaster(Request $request): void
    {
        $engine = new ReportEngine();
        $suppliers = $engine->getSupplierList();
        View::make("reports/supplier/master", [
            "title" => "Supplier Master List",
            "suppliers" => $suppliers,
        ]);
    }

    public function supplierLedger(Request $request): void
    {
        $engine = new ReportEngine();
        $suppliers = $engine->getSupplierList();
        View::make("reports/supplier/ledger", [
            "title" => "Supplier Ledger Statement",
            "suppliers" => $suppliers,
        ]);
    }

    // ── Customer ──────────────────────────────────────────────

    public function customerMaster(Request $request): void
    {
        $engine = new ReportEngine();
        $customers = $engine->getCustomerList();
        View::make("reports/customer/master", [
            "title" => "Customer Master List",
            "customers" => $customers,
        ]);
    }

    public function customerLedger(Request $request): void
    {
        $engine = new ReportEngine();
        $customers = $engine->getCustomerList();
        View::make("reports/customer/ledger", [
            "title" => "Customer Ledger Statement",
            "customers" => $customers,
        ]);
    }

    // ── User / Security ───────────────────────────────────────

    public function userMaster(Request $request): void
    {
        View::make("reports/user/master", [
            "title" => "System Users List",
        ]);
    }

    public function userSales(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();
        View::make("reports/user/sales", [
            "title" => "User Sales Report",
            "shops" => $shops,
        ]);
    }

    public function userSecurity(Request $request): void
    {
        View::make("reports/user/security", [
            "title" => "Security Audit Log",
        ]);
    }

    // ── GRN Reports ───────────────────────────────────────────

    public function grnList(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();
        View::make("reports/grn/list", [
            "title" => "GRN List",
            "shops" => $shops,
        ]);
    }

    public function grnDetail(Request $request): void
    {
        View::make("reports/grn/detail", [
            "title" => "GRN Detail",
        ]);
    }

    public function grnReorder(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();
        $catModel = new Category();
        $categories = $catModel->all();
        View::make("reports/grn/reorder", [
            "title" => "Reorder Alert Report",
            "shops" => $shops,
            "categories" => $categories,
        ]);
    }

    // ── Inventory Logs ────────────────────────────────────────

    public function logsPriceEdit(Request $request): void
    {
        View::make("reports/logs/price_edit", [
            "title" => "Price Edit Log",
        ]);
    }

    public function logsStockEdit(Request $request): void
    {
        View::make("reports/logs/stock_edit", [
            "title" => "Stock Edit Log",
        ]);
    }

    public function logsStockDelete(Request $request): void
    {
        View::make("reports/logs/stock_delete", [
            "title" => "Stock Delete Log",
        ]);
    }

    // ── Repair Reports ─────────────────────────────────────────

    public function repairJobList(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();
        View::make("reports/repair/jobs", [
            "title" => "Repair Job List",
            "shops" => $shops,
        ]);
    }

    public function repairJobDetail(Request $request): void
    {
        View::make("reports/repair/detail", [
            "title" => "Repair Job Detail",
        ]);
    }

    // ── Stock Transfer Reports ─────────────────────────────────

    public function transferList(Request $request): void
    {
        $shopModel = new Shop();
        $shops = $shopModel->all();
        View::make("reports/transfer/list", [
            "title" => "Stock Transfer List",
            "shops" => $shops,
        ]);
    }

    public function transferDetail(Request $request): void
    {
        View::make("reports/transfer/detail", [
            "title" => "Transfer Detail",
        ]);
    }

    public function transferLogCheck(Request $request): void
    {
        View::make("reports/transfer/logcheck", [
            "title" => "Item Transfer Log Check",
        ]);
    }

    public function fetch(Request $request): void
    {
        $reportType = (string) $request->input("type", "");
        $fromDate = (string) $request->input("from_date", date("Y-m-d"));
        $toDate = (string) $request->input("to_date", date("Y-m-d"));
        $shopId = (int) $request->input("shop_id", -1);

        $fromDate = str_replace('-', '', $fromDate); // Convert 2026-05-03 to 20260503 to match old DB style if needed
        $toDate = str_replace('-', '', $toDate);

        $engine = new ReportEngine();

        try {
            if ($reportType === "shop_sale") {
                $data = $engine->getShopSaleReport($fromDate, $toDate, $shopId);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "category_sale") {
                $categoryId = (int) $request->input("category_id", -1);
                $data = $engine->getCategorySaleReport($fromDate, $toDate, $shopId, $categoryId);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "cashier_transactions") {
                $data = $engine->getCashierTransactions($fromDate, $toDate, $shopId);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "cashier_openclose") {
                $data = $engine->getOpenCloseBalances($fromDate, $toDate, $shopId);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "cashier_expenses") {
                $data = $engine->getShopExpenses($fromDate, $toDate, $shopId);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "cashier_profit") {
                $data = $engine->getShopProfit($fromDate, $toDate, $shopId);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "product_categories") {
                $data = $engine->getProductCategories();
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "product_list") {
                $categoryId = (int) $request->input("category_id", -1);
                $data = $engine->getProductList($categoryId);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "product_stock") {
                $categoryId = (int) $request->input("category_id", -1);
                $availability = (string) $request->input("availability", "all");
                $data = $engine->getComprehensiveStock($shopId, $categoryId, $availability);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "supplier_master") {
                $data = $engine->getSupplierMaster();
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "supplier_ledger") {
                $supplierId = (int) $request->input("supplier_id", 0);
                $data = $engine->getSupplierLedger($supplierId, $fromDate, $toDate);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "customer_master") {
                $data = $engine->getCustomerMaster();
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "customer_ledger") {
                $customerId = (int) $request->input("customer_id", 0);
                $data = $engine->getCustomerLedger($customerId, $fromDate, $toDate);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "user_master") {
                $data = $engine->getUserMaster();
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "user_sales") {
                $data = $engine->getUserSales($shopId, $fromDate, $toDate);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "security_log") {
                $data = $engine->getSecurityLog($fromDate, $toDate);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "grn_list") {
                $data = $engine->getGrnList($shopId, $fromDate, $toDate);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "grn_detail") {
                $grnRefNo = (string) $request->input("grn_refno", "");
                $data = $engine->getGrnDetail($grnRefNo);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "grn_reorder") {
                $categoryId = (int) $request->input("category_id", -1);
                $data = $engine->getGrnReorder($shopId, $categoryId);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "logs_price_edit") {
                $data = $engine->getPriceEditLog($fromDate, $toDate);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "logs_stock_edit") {
                $data = $engine->getStockEditLog($fromDate, $toDate);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "logs_stock_delete") {
                $data = $engine->getStockDeleteLog($fromDate, $toDate);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "repair_jobs") {
                $statusFilter = $request->input("status_filter") !== null ? (int) $request->input("status_filter") : null;
                $data = $engine->getRepairJobs($shopId, $fromDate, $toDate, $statusFilter);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "repair_job_detail") {
                $jobNumber = (string) $request->input("job_number", "");
                $data = $engine->getRepairJobDetail($jobNumber);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "transfer_list") {
                $data = $engine->getTransferList($shopId, $fromDate, $toDate);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "transfer_detail") {
                $transId = (string) $request->input("trans_id", "");
                $data = $engine->getTransferDetail($transId);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            if ($reportType === "transfer_logcheck") {
                $itemCode = (string) $request->input("item_code", "");
                $data = $engine->getTransferLogCheck($itemCode);
                echo json_encode(["status" => "success", "data" => $data]);
                return;
            }

            echo json_encode(["status" => "error", "message" => "Unknown report type."]);
        } catch (Throwable $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }
}
