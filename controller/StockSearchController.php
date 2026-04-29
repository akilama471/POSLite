<?php

declare(strict_types=1);

class StockSearchController
{
    public function index(Request $request): void
    {
        $itemModel = new Item();
        $categoryModel = new ProductCategory();

        $category = trim((string) $request->input("category", ""));
        $name = trim((string) $request->input("name", ""));
        $code = trim((string) $request->input("code", ""));
        $errors = [];
        $results = [];

        if ($name !== "" && $code !== "") {
            $errors[] = "Use either item name or barcode, not both.";
        } elseif ($name !== "" || $code !== "") {
            $results = $itemModel->stockSearch($name, $code);
        }

        View::make("catalog/items/search", [
            "title" => "Search Items",
            "auth" => auth_user(),
            "categories" => $categoryModel->allOrdered(),
            "filters" => [
                "category" => $category,
                "name" => $name,
                "code" => $code,
            ],
            "results" => $results,
            "errors" => $errors,
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function itemsByCategory(Request $request): void
    {
        $itemModel = new Item();
        $items = $itemModel->namesByCategoryName(
            trim((string) $request->input("category", "")),
        );

        json_response($items);
    }
}
