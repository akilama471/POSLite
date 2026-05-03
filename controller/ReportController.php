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

            echo json_encode(["status" => "error", "message" => "Unknown report type."]);
        } catch (Throwable $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }
}
