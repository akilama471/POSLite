<?php

declare(strict_types=1);

class ItemColorController
{
    public function index(Request $request): void
    {
        $model = new ItemColor();

        View::make("catalog/colors/index", [
            "title" => "Manage Item Colors",
            "auth" => auth_user(),
            "colors" => $model->allOrdered(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/item-colors");
        }

        $name = trim((string) $request->input("name", ""));
        $model = new ItemColor();

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Color name is required."];
            redirect("/item-colors");
        }

        if ($model->existsByName($name)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That color already exists."];
            redirect("/item-colors");
        }

        $model->createColor($name);
        $_SESSION["flash"] = ["type" => "success", "message" => "Color created successfully."];
        redirect("/item-colors");
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/item-colors");
        }

        $name = trim((string) $request->input("name", ""));
        $colorId = (int) $id;
        $model = new ItemColor();

        if ($name === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Color name is required."];
            redirect("/item-colors");
        }

        if ($model->existsByName($name, $colorId)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That color already exists."];
            redirect("/item-colors");
        }

        $model->updateColor($colorId, $name);
        $_SESSION["flash"] = ["type" => "success", "message" => "Color updated successfully."];
        redirect("/item-colors");
    }

    public function destroy(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/item-colors");
        }

        $model = new ItemColor();
        $model->deleteColor((int) $id);

        $_SESSION["flash"] = ["type" => "success", "message" => "Color deleted successfully."];
        redirect("/item-colors");
    }
}
