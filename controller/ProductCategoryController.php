<?php

declare(strict_types=1);

class ProductCategoryController
{
    public function index(Request $request): void
    {
        $categoryModel = new ProductCategory();

        View::make("catalog/categories/index", [
            "title" => "Manage Categories",
            "auth" => auth_user(),
            "categories" => $categoryModel->allOrdered(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/categories");
        }

        $name = trim((string) $request->input("name", ""));
        $categoryModel = new ProductCategory();

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Category name is required."];
            redirect("/categories");
        }

        if ($categoryModel->existsByName($name)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That category already exists."];
            redirect("/categories");
        }

        $categoryModel->createCategory($name);
        $_SESSION["flash"] = ["type" => "success", "message" => "Category created successfully."];
        redirect("/categories");
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/categories");
        }

        $name = trim((string) $request->input("name", ""));
        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Category name is required."];
            redirect("/categories");
        }

        $categoryModel = new ProductCategory();
        $categoryModel->updateCategory((int) $id, $name);

        $_SESSION["flash"] = ["type" => "success", "message" => "Category updated successfully."];
        redirect("/categories");
    }

    public function destroy(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/categories");
        }

        $categoryModel = new ProductCategory();
        $categoryModel->deleteCategory((int) $id);

        $_SESSION["flash"] = ["type" => "success", "message" => "Category deleted successfully."];
        redirect("/categories");
    }
}
