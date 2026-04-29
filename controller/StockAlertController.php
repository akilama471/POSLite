<?php

declare(strict_types=1);

class StockAlertController
{
    public function index(Request $request): void
    {
        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $model = new StockAlert();
        $categoryModel = new ProductCategory();

        View::make("catalog/alerts/index", [
            "title" => "Item Alert Configuration",
            "auth" => $auth,
            "alerts" => $model->activeByShop($shopId),
            "categories" => $categoryModel->allOrdered(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/item-alerts");
        }

        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $itemName = trim((string) $request->input("item_name", ""));
        $alertQty = (int) $request->input("alert_qty", 0);
        $itemModel = new Item();
        $alertModel = new StockAlert();

        if ($itemName === "" || $alertQty <= 0) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Item name and alert quantity are required."];
            redirect("/item-alerts");
        }

        $item = $itemModel->findByName($itemName);
        if ($item === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Item not found."];
            redirect("/item-alerts");
        }

        if ($alertModel->existsActiveForItem($shopId, (int) $item["item_id"])) {
            $_SESSION["flash"] = ["type" => "error", "message" => "This item alert count is already configured."];
            redirect("/item-alerts");
        }

        $alertModel->createAlert($shopId, $item, $alertQty);
        $_SESSION["flash"] = ["type" => "success", "message" => "Item alert created successfully."];
        redirect("/item-alerts");
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/item-alerts");
        }

        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $alertQty = (int) $request->input("alert_qty", 0);

        if ($alertQty <= 0) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Alert quantity must be greater than zero."];
            redirect("/item-alerts");
        }

        $model = new StockAlert();
        $existing = $model->findActiveById((int) $id, $shopId);

        if ($existing === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Alert record not found."];
            redirect("/item-alerts");
        }

        $model->updateAlertQty((int) $id, $shopId, $alertQty);
        $_SESSION["flash"] = ["type" => "success", "message" => "Alert quantity updated successfully."];
        redirect("/item-alerts");
    }

    public function destroy(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/item-alerts");
        }

        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $model = new StockAlert();
        $model->expireAlert((int) $id, $shopId);

        $_SESSION["flash"] = ["type" => "success", "message" => "Alert record removed successfully."];
        redirect("/item-alerts");
    }
}
