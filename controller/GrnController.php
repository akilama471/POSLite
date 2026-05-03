<?php

declare(strict_types=1);

class GrnController
{
    public function index(Request $request): void
    {
        $auth = auth_user() ?? [];
        $authShopId = (int) ($auth["shop_id"] ?? 0);
        $shopModel = new Shop();
        $grnModel = new Grn();

        $filters = [
            "grn_id" => trim((string) $request->input("grn_id", "")),
            "shop_id" => (int) $request->input("shop_id", -1),
            "supplier" => trim((string) $request->input("supplier", "")),
            "item_name" => trim((string) $request->input("item_name", "")),
            "imei" => trim((string) $request->input("imei", "")),
            "start_date" => trim((string) $request->input("start_date", "")),
            "end_date" => trim((string) $request->input("end_date", "")),
        ];

        if ($authShopId > 0) {
            $filters["shop_id"] = $authShopId;
        }

        View::make("grns/index", [
            "title" => "Find GRN",
            "auth" => $auth,
            "filters" => $filters,
            "shops" => $authShopId > 0 ? array_filter($shopModel->allOrdered(), static fn (array $shop): bool => (int) ($shop["shopid"] ?? 0) === $authShopId) : $shopModel->allOrdered(),
            "results" => $grnModel->search($filters, $authShopId),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function create(Request $request): void
    {
        $auth = auth_user() ?? [];
        $authShopId = (int) ($auth["shop_id"] ?? 0);
        $shopModel = new Shop();
        $grnModel = new Grn();
        $supplierModel = new Supplier();
        $categoryModel = new ProductCategory();

        View::make("grns/create", [
            "title" => "Add GRN",
            "auth" => $auth,
            "draft" => $grnModel->draft($auth),
            "suppliers" => $supplierModel->allOrdered(),
            "shops" => $authShopId > 0 ? array_filter($shopModel->allOrdered(), static fn (array $shop): bool => (int) ($shop["shopid"] ?? 0) === $authShopId) : $shopModel->allOrdered(),
            "categories" => $categoryModel->allOrdered(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function itemDetails(Request $request): void
    {
        $auth = auth_user() ?? [];
        $grnModel = new Grn();
        $details = $grnModel->itemDraftDetailsByName(
            trim((string) $request->input("name", "")),
            (int) ($auth["shop_id"] ?? 0),
        );

        json_response($details ?? ["found" => false]);
    }

    public function updateHeader(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/grns/create");
        }

        $grnModel = new Grn();

        try {
            $grnModel->updateDraftHeader(auth_user() ?? [], [
                "supplier_id" => (int) $request->input("supplier_id", 0),
                "invoice_number" => trim((string) $request->input("invoice_number", "")),
            ]);
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
            redirect("/grns/create");
        }

        $_SESSION["flash"] = ["type" => "success", "message" => "GRN header updated."];
        redirect("/grns/create");
    }

    public function addLine(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/grns/create");
        }

        $grnModel = new Grn();

        try {
            $grnModel->addDraftLine(auth_user() ?? [], [
                "category_name" => trim((string) $request->input("category_name", "")),
                "item_name" => trim((string) $request->input("item_name", "")),
                "imei_no" => trim((string) $request->input("imei_no", "")),
                "item_color" => trim((string) $request->input("item_color", "")),
                "qty" => (int) $request->input("qty", 0),
                "cost_price" => (float) $request->input("cost_price", 0),
                "sell_price" => (float) $request->input("sell_price", 0),
                "low_price" => (float) $request->input("low_price", 0),
                "other_price" => (float) $request->input("other_price", 0),
                "item_free" => (int) $request->input("item_free", 0),
                "stock_shop_id" => (int) $request->input("stock_shop_id", 0),
                "warranty_span" => (int) $request->input("warranty_span", 0),
                "warranty_type" => trim((string) $request->input("warranty_type", "")),
            ]);
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
            redirect("/grns/create");
        }

        $_SESSION["flash"] = ["type" => "success", "message" => "GRN line added."];
        redirect("/grns/create");
    }

    public function removeLine(Request $request, string $index): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/grns/create");
        }

        $grnModel = new Grn();

        try {
            $grnModel->removeDraftLine(auth_user() ?? [], (int) $index);
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
            redirect("/grns/create");
        }

        $_SESSION["flash"] = ["type" => "success", "message" => "GRN line removed."];
        redirect("/grns/create");
    }

    public function clear(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/grns/create");
        }

        $grnModel = new Grn();
        $grnModel->clearDraft(auth_user() ?? []);
        $_SESSION["flash"] = ["type" => "success", "message" => "Current GRN draft cleared."];
        redirect("/grns/create");
    }

    public function submit(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/grns/create");
        }

        $grnModel = new Grn();

        try {
            $grnRefNo = $grnModel->finalizeDraft(auth_user() ?? [], [
                "discount_amount" => (float) $request->input("discount_amount", 0),
                "discount_percent" => (float) $request->input("discount_percent", 0),
                "cash_amount" => (float) $request->input("cash_amount", 0),
                "cheque_amount" => (float) $request->input("cheque_amount", 0),
                "cheque_number" => trim((string) $request->input("cheque_number", "")),
                "cheque_date" => trim((string) $request->input("cheque_date", "")),
                "cheque_reminder" => (int) $request->input("cheque_reminder", 0),
                "cheque_reminder_date" => trim((string) $request->input("cheque_reminder_date", "")),
            ]);
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
            redirect("/grns/create");
        }

        $_SESSION["flash"] = ["type" => "success", "message" => "GRN " . $grnRefNo . " submitted successfully."];
        redirect("/grns");
    }
}
