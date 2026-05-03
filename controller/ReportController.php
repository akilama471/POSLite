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

            echo json_encode(["status" => "error", "message" => "Unknown report type."]);
        } catch (Throwable $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
    }
}
