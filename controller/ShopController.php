<?php

declare(strict_types=1);

class ShopController
{
    public function index(Request $request): void
    {
        $shopModel = new Shop();

        View::make("settings/shops/index", [
            "title" => "Shop List",
            "auth" => auth_user(),
            "shops" => $shopModel->allOrdered(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function create(Request $request): void
    {
        $shopModel = new Shop();

        View::make("settings/shops/create", [
            "title" => "Add Shop",
            "auth" => auth_user(),
            "nextShopId" => $shopModel->nextShopId(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function store(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/settings/shops/create");
        }

        $shopModel = new Shop();
        $shopName = trim((string) $request->input("shopname", ""));
        $shopInfoName = trim((string) $request->input("shop_info_name", ""));
        $address = trim((string) $request->input("shopaddress", ""));
        $telephone = trim((string) $request->input("shop_tel_1", ""));
        $posUniq = strtoupper(trim((string) $request->input("pos_uniq", "")));

        if ($shopName === "" || $shopInfoName === "" || $address === "" || $telephone === "" || $posUniq === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Shop name, info name, address, telephone, and bill unique ID are required."];
            redirect("/settings/shops/create");
        }

        if ($shopModel->existsByName($shopName)) {
            $_SESSION["flash"] = ["type" => "error", "message" => "That shop name is already registered."];
            redirect("/settings/shops/create");
        }

        $shopModel->createShop([
            "shopid" => $shopModel->nextShopId(),
            "shopname" => $shopName,
            "shop_info_name" => $shopInfoName,
            "shopaddress" => $address,
            "shop_tel_1" => $telephone,
            "shop_fax" => trim((string) $request->input("shop_fax", "")),
            "shopemail" => trim((string) $request->input("shopemail", "")),
            "bill_foot_1" => trim((string) $request->input("bill_foot_1", "")),
            "bill_foot_2" => trim((string) $request->input("bill_foot_2", "")),
            "pos_uniq" => $posUniq,
            "addeddate" => date("Y-m-d H:i:s"),
        ]);

        $_SESSION["flash"] = ["type" => "success", "message" => "Shop created successfully."];
        redirect("/settings/shops");
    }

    public function edit(Request $request, string $id): void
    {
        $shopModel = new Shop();
        $shop = $shopModel->findByShopId((int) $id);

        if ($shop === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Shop not found."];
            redirect("/settings/shops");
        }

        View::make("settings/shops/edit", [
            "title" => "Edit Shop",
            "auth" => auth_user(),
            "shop" => $shop,
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function update(Request $request, string $id): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/settings/shops/" . (int) $id . "/edit");
        }

        $shopId = (int) $id;
        $shopModel = new Shop();
        $shopModel->updateShop($shopId, [
            "shopname" => trim((string) $request->input("shopname", "")),
            "shop_info_name" => trim((string) $request->input("shop_info_name", "")),
            "shopaddress" => trim((string) $request->input("shopaddress", "")),
            "shop_tel_1" => trim((string) $request->input("shop_tel_1", "")),
            "shop_fax" => trim((string) $request->input("shop_fax", "")),
            "shopemail" => trim((string) $request->input("shopemail", "")),
            "bill_foot_1" => trim((string) $request->input("bill_foot_1", "")),
            "bill_foot_2" => trim((string) $request->input("bill_foot_2", "")),
        ]);

        $_SESSION["flash"] = ["type" => "success", "message" => "Shop updated successfully."];
        redirect("/settings/shops");
    }
}
