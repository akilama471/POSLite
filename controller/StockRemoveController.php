<?php

declare(strict_types=1);

class StockRemoveController
{
    public function index(Request $request): void
    {
        $auth = auth_user() ?? [];
        $categoryModel = new ProductCategory();

        View::make("stock/remove/index", [
            "title" => "Stock Removal",
            "auth" => $auth,
            "categories" => $categoryModel->allOrdered(),
            "candidates" => $_SESSION["stock_remove_candidates"] ?? [],
            "search" => $_SESSION["stock_remove_search"] ?? ["mode" => "code", "query" => ""],
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"], $_SESSION["stock_remove_candidates"], $_SESSION["stock_remove_search"]);
    }

    public function search(Request $request): void
    {
        if (!$this->validCsrf($request, "/stock/remove")) {
            return;
        }

        $auth = auth_user() ?? [];
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $mode = trim((string) $request->input("search_mode", "code"));
        $query = trim((string) $request->input("search_query", ""));
        $model = new StockRemove();

        $_SESSION["stock_remove_search"] = [
            "mode" => $mode,
            "query" => $query,
        ];

        if ($query === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Enter a code or item name to search."];
            redirect("/stock/remove");
        }

        try {
            $_SESSION["stock_remove_candidates"] = $mode === "name"
                ? $model->findCandidatesByItemName($query, $shopId)
                : $model->findCandidatesByCode($query, $shopId);

            if ($_SESSION["stock_remove_candidates"] === []) {
                $_SESSION["flash"] = ["type" => "error", "message" => "No stock records matched the current search."];
            }
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/remove");
    }

    public function submit(Request $request): void
    {
        if (!$this->validCsrf($request, "/stock/remove")) {
            return;
        }

        try {
            (new StockRemove())->removeStock(
                auth_user() ?? [],
                (int) $request->input("object_type", 0),
                (int) $request->input("row_id", 0),
                trim((string) $request->input("reason", ""))
            );
            $_SESSION["flash"] = ["type" => "success", "message" => "Stock removed successfully."];
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
        }

        redirect("/stock/remove");
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
