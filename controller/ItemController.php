<?php

declare(strict_types=1);

class ItemController
{
    public function create(Request $request): void
    {
        $itemModel = new Item();
        $context = $itemModel->createContext();

        View::make("catalog/items/create", [
            "title" => "Add Item",
            "auth" => auth_user(),
            "categories" => $context["categories"],
            "operators" => $context["operators"],
            "existingItems" => $context["existingItems"],
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/items/create");
        }

        $itemModel = new Item();
        $itemName = trim((string) $request->input("item_name", ""));
        $categoryId = (int) $request->input("item_cat", 0);
        $usedType = (int) $request->input("used_type", 0);
        $operatorId = (int) $request->input("operator_id", 0);
        $cardRemark = trim((string) $request->input("card_remark", ""));

        if ($itemName === "" || $categoryId <= 0 || !in_array($usedType, [1, 2, 3], true)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Category, item name, and control type are required."];
            redirect("/items/create");
        }

        if ($usedType === 3 && $operatorId <= 0) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Recharge card items require an operator."];
            redirect("/items/create");
        }

        if ($itemModel->existsByName($itemName)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That item already exists."];
            redirect("/items/create");
        }

        $itemModel->createItem([
            "item_name" => $itemName,
            "item_cat" => $categoryId,
            "used_type" => $usedType,
            "operator_id" => $operatorId,
            "card_remark" => $cardRemark,
            "eff_date" => date("Y-m-d H:i:s"),
        ]);

        $_SESSION["flash"] = ["type" => "success", "message" => "Item created successfully."];
        redirect(can("p_16") ? "/items" : "/items/create");
    }

    public function index(Request $request): void
    {
        $itemModel = new Item();
        $search = trim((string) $request->input("name", ""));

        View::make("catalog/items/index", [
            "title" => "Edit Item",
            "auth" => auth_user(),
            "search" => $search,
            "items" => $itemModel->searchDetailed($search),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function edit(Request $request, string $id): void
    {
        $itemModel = new Item();
        $item = $itemModel->findDetailedById((int) $id);

        if ($item === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Item not found."];
            redirect("/items");
        }

        View::make("catalog/items/edit", [
            "title" => "Edit Item",
            "auth" => auth_user(),
            "item" => $item,
            "typeLabel" => Item::typeLabel((int) $item["used_type"]),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/items/" . (int) $id . "/edit");
        }

        $itemId = (int) $id;
        $itemModel = new Item();
        $item = $itemModel->findDetailedById($itemId);

        if ($item === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Item not found."];
            redirect("/items");
        }

        $itemName = trim((string) $request->input("item_name", ""));
        $cardRemark = trim((string) $request->input("card_remark", ""));

        if ($itemName === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Item name is required."];
            redirect("/items/" . $itemId . "/edit");
        }

        if ($itemModel->existsByName($itemName, $itemId)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That item name is already in use."];
            redirect("/items/" . $itemId . "/edit");
        }

        $itemModel->updateItem($itemId, [
            "item_name" => $itemName,
            "used_type" => (int) $item["used_type"],
            "card_remark" => $cardRemark,
        ]);

        $_SESSION["flash"] = ["type" => "success", "message" => "Item updated successfully."];
        redirect("/items");
    }
}
