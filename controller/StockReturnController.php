<?php

declare(strict_types=1);

class StockReturnController
{
    public function create(Request $request): void
    {
        $auth = auth_user() ?? [];
        $categoryModel = new ProductCategory();

        View::make("stock/returns/create", [
            "title" => "Stock Return to Supplier",
            "auth" => $auth,
            "categories" => $categoryModel->allOrdered(),
            "candidates" => $_SESSION["stock_return_candidates"] ?? [],
            "search" => $_SESSION["stock_return_search"] ?? ["mode" => "code", "query" => ""],
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"], $_SESSION["stock_return_candidates"], $_SESSION["stock_return_search"]);
    }

    public function search(Request $request): void
    {
        if (!$this->validCsrf($request, "/stock/returns/create")) {
            return;
        }

        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $mode = trim((string) $request->input("search_mode", "code"));
        $query = trim((string) $request->input("search_query", ""));
        $model = new StockReturn();

        $_SESSION["stock_return_search"] = [
            "mode" => $mode,
            "query" => $query,
        ];

        if ($query === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Enter a code or item name to search."];
            redirect("/stock/returns/create");
        }

        try {
            $_SESSION["stock_return_candidates"] = $mode === "name"
                ? $model->findCandidatesByItemName($query, $shopId)
                : $model->findCandidatesByCode($query, $shopId);

            if ($_SESSION["stock_return_candidates"] === []) {
                $_SESSION["flash"] = ["type" => "error", "message" => "No stock records matched the current search."];
            }
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/returns/create");
    }

    public function submit(Request $request): void
    {
        if (!$this->validCsrf($request, "/stock/returns/create")) {
            return;
        }

        try {
            (new StockReturn())->returnStock(
                auth_user() ?? [],
                (int) $request->input("object_type", 0),
                (int) $request->input("row_id", 0),
                (int) $request->input("qty", 0),
                trim((string) $request->input("reason", ""))
            );
            $_SESSION["flash"] = ["type" => "success", "message" => "Stock successfully returned to supplier."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/returns/create");
    }

    private function validCsrf(Request $request, string $redirectPath): bool
    {
        if (verify_csrf((string) $request->input("_token"))) {
            return true;
        }

        $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
        redirect($redirectPath);
    }
}
