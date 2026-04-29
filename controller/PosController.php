<?php

declare(strict_types=1);

class PosController
{
    public function index(Request $request): void
    {
        $categoryModel = new ProductCategory();

        View::make("pos/index", [
            "title" => "Point Of Sale",
            "auth" => auth_user(),
            "categories" => $categoryModel->allOrdered(),
            "cart" => $this->cart(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function lookupByName(Request $request): void
    {
        $itemModel = new Item();
        $auth = auth_user() ?? [];
        $result = $itemModel->posLookupByName(
            trim((string) $request->input("item_name", "")),
            (int) ($auth["shop_id"] ?? 0),
        );

        json_response([
            "found" => $result["itm_selectid"] !== "",
            "item" => $this->normalizeLookupResult($result),
        ]);
    }

    public function lookupByCode(Request $request): void
    {
        $itemModel = new Item();
        $auth = auth_user() ?? [];
        $result = $itemModel->posLookupByCode(
            trim((string) $request->input("item_code", "")),
            (int) ($auth["shop_id"] ?? 0),
        );

        json_response([
            "found" => $result["itm_selectid"] !== "",
            "item" => $this->normalizeLookupResult($result),
        ]);
    }

    public function selectCustomer(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $customerId = (int) $request->input("customer_id", 0);
        $customerName = trim((string) $request->input("customer_name", "Cash Customer"));
        $cart = $this->cart();
        $cart["customer"] = [
            "id" => $customerId,
            "name" => $customerName === "" ? "Cash Customer" : $customerName,
        ];
        $this->storeCart($cart);

        $_SESSION["flash"] = ["type" => "success", "message" => "Customer updated for the current bill."];
        redirect("/pos");
    }

    public function addItem(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $lookup = $this->normalizeLookupResult(json_decode((string) $request->input("lookup_payload", "{}"), true) ?: []);

        if (($lookup["item_id"] ?? "") === "") {
            $_SESSION["flash"] = ["type" => "error", "message" => "Select a valid stock item before adding to bill."];
            redirect("/pos");
        }

        $qty = max(1, (int) $request->input("qty", 1));
        $type = (int) ($lookup["type"] ?? 0);
        if ($type === 2) {
            $qty = 1;
        }

        $salePrice = (float) $request->input("sale_price", $lookup["sell_price"] ?? 0);
        $discount = max(0, (float) $request->input("discount", 0));
        $warranty = trim((string) $request->input("warranty", (string) ($lookup["warranty"] ?? "")));

        $cart = $this->cart();
        $cart["lines"][] = $this->buildCartLine($lookup, $qty, $salePrice, $discount, $warranty);
        $this->storeCart($cart);

        $_SESSION["flash"] = ["type" => "success", "message" => "Item added to the current bill."];
        redirect("/pos");
    }

    public function updateLine(Request $request, string $index): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $lineIndex = (int) $index;
        $cart = $this->cart();

        if (!isset($cart["lines"][$lineIndex])) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Cart line not found."];
            redirect("/pos");
        }

        $line = $cart["lines"][$lineIndex];
        $qty = max(1, (int) $request->input("qty", (int) $line["qty"]));
        if ((int) $line["type"] === 2) {
            $qty = 1;
        }

        $cart["lines"][$lineIndex] = $this->buildCartLine(
            $line,
            $qty,
            (float) $request->input("sale_price", (float) $line["sale_price"]),
            max(0, (float) $request->input("discount", (float) $line["discount"])),
            trim((string) $request->input("warranty", (string) $line["warranty"])),
        );

        $this->storeCart($cart);
        $_SESSION["flash"] = ["type" => "success", "message" => "Cart line updated."];
        redirect("/pos");
    }

    public function removeLine(Request $request, string $index): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $lineIndex = (int) $index;
        $cart = $this->cart();

        if (isset($cart["lines"][$lineIndex])) {
            array_splice($cart["lines"], $lineIndex, 1);
            $this->storeCart($cart);
            $_SESSION["flash"] = ["type" => "success", "message" => "Cart line removed."];
        }

        redirect("/pos");
    }

    public function resetCart(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        unset($_SESSION["pos_cart"]);
        $_SESSION["flash"] = ["type" => "success", "message" => "Current bill cleared."];
        redirect("/pos");
    }

    public function updatePayment(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $cart = $this->cart();
        $cart["payment"] = [
            "method" => (string) $request->input("method", "cash"),
            "cash_amount" => max(0, (float) $request->input("cash_amount", 0)),
            "card_amount" => max(0, (float) $request->input("card_amount", 0)),
            "card_number" => trim((string) $request->input("card_number", "")),
        ];
        $this->storeCart($cart);

        $_SESSION["flash"] = ["type" => "success", "message" => "Payment details staged for this bill."];
        redirect("/pos");
    }

    public function checkout(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $auth = auth_user() ?? [];
        $cart = $this->cart();

        if (($cart["lines"] ?? []) === []) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Add at least one item before finishing the bill."];
            redirect("/pos");
        }

        $validation = $this->validateCheckout($cart);
        if ($validation !== null) {
            $_SESSION["flash"] = ["type" => "error", "message" => $validation];
            redirect("/pos");
        }

        $shopModel = new Shop();
        $shop = $shopModel->findByShopId((int) ($auth["shop_id"] ?? 0));

        if ($shop === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Shop configuration not found for POS checkout."];
            redirect("/pos");
        }

        $saleModel = new PosSale();

        try {
            $billNumber = $saleModel->checkout([
                "shop_id" => (int) ($auth["shop_id"] ?? 0),
                "user_id" => (int) ($auth["user_id"] ?? 0),
                "seller_name" => (string) ($auth["display_name"] ?? $auth["username"] ?? "User"),
                "shop" => $shop,
                "customer" => $cart["customer"],
                "lines" => $cart["lines"],
                "payment" => $cart["payment"],
                "summary" => $cart["summary"],
            ]);
        } catch (Throwable $exception) {
            $_SESSION["flash"] = ["type" => "error", "message" => $exception->getMessage()];
            redirect("/pos");
        }

        unset($_SESSION["pos_cart"]);
        redirect("/pos/receipts/" . $billNumber);
    }

    public function receipt(Request $request, string $billNumber): void
    {
        $saleModel = new PosSale();
        $shopModel = new Shop();
        $customerModel = new Customer();
        $userModel = new User();
        $receipt = $saleModel->receipt($billNumber);

        if ($receipt === null) {
            http_response_code(404);
            View::make("errors/404", ["title" => "Receipt Not Found"]);
            return;
        }

        $bill = $receipt["bill"];
        $customer = (int) ($bill["customer_id"] ?? 0) > 0
            ? $customerModel->findById((int) $bill["customer_id"])
            : null;
        $cashier = $userModel->findById((int) ($bill["operator"] ?? 0));
        $shop = $shopModel->findByShopId((int) ($bill["billed_shop"] ?? 0));

        View::make("pos/receipt", [
            "title" => "POS Receipt",
            "auth" => auth_user(),
            "bill" => $bill,
            "lines" => $receipt["lines"],
            "customer" => $customer,
            "cashier" => $cashier,
            "shop" => $shop,
        ]);
    }

    private function cart(): array
    {
        $cart = $_SESSION["pos_cart"] ?? null;

        if (!is_array($cart)) {
            $cart = [
                "customer" => ["id" => 0, "name" => "Cash Customer"],
                "lines" => [],
                "payment" => [
                    "method" => "cash",
                    "cash_amount" => 0.0,
                    "card_amount" => 0.0,
                    "card_number" => "",
                ],
            ];
        }

        $cart["summary"] = $this->summary($cart["lines"], $cart["payment"]);

        return $cart;
    }

    private function storeCart(array $cart): void
    {
        $cart["summary"] = $this->summary($cart["lines"] ?? [], $cart["payment"] ?? []);
        $_SESSION["pos_cart"] = $cart;
    }

    private function summary(array $lines, array $payment): array
    {
        $total = 0.0;

        foreach ($lines as $line) {
            $total += (float) ($line["sub_total"] ?? 0);
        }

        $cash = (float) ($payment["cash_amount"] ?? 0);
        $card = (float) ($payment["card_amount"] ?? 0);

        return [
            "total" => $total,
            "paid" => $cash + $card,
            "balance" => ($cash + $card) - $total,
        ];
    }

    private function validateCheckout(array $cart): ?string
    {
        $payment = $cart["payment"] ?? [];
        $summary = $cart["summary"] ?? ["total" => 0, "paid" => 0, "balance" => 0];
        $method = (string) ($payment["method"] ?? "cash");
        $customerId = (int) (($cart["customer"]["id"] ?? 0));

        if ($method === "cash" && (float) ($payment["cash_amount"] ?? 0) <= 0) {
            return "Cash amount is required.";
        }

        if ($method === "card" && ((float) ($payment["card_amount"] ?? 0) <= 0 || trim((string) ($payment["card_number"] ?? "")) === "")) {
            return "Card amount and card number are required.";
        }

        if ($method === "split" && (((float) ($payment["cash_amount"] ?? 0) + (float) ($payment["card_amount"] ?? 0)) <= 0 || trim((string) ($payment["card_number"] ?? "")) === "")) {
            return "Split payment requires payment amounts and a card number.";
        }

        if ($customerId === 0 && (float) ($summary["paid"] ?? 0) < (float) ($summary["total"] ?? 0)) {
            return "Cash customer bills must be fully paid before checkout.";
        }

        return null;
    }

    private function buildCartLine(array $lookup, int $qty, float $salePrice, float $discount, string $warranty): array
    {
        $subTotal = max(0, ($salePrice - $discount) * $qty);

        return [
            "item_id" => (string) ($lookup["item_id"] ?? ""),
            "cat_id" => (string) ($lookup["cat_id"] ?? ""),
            "type" => (string) ($lookup["type"] ?? ""),
            "item_name" => (string) ($lookup["name"] ?? ""),
            "code" => (string) ($lookup["code"] ?? ""),
            "cost_price" => (float) ($lookup["cost_price"] ?? 0),
            "qty" => $qty,
            "sale_price" => $salePrice,
            "discount" => $discount,
            "sub_total" => $subTotal,
            "warranty" => $warranty,
            "row_id" => (string) ($lookup["row_id"] ?? ""),
            "supplier_id" => (string) ($lookup["supplier_id"] ?? ""),
            "stock_total" => (string) ($lookup["stock_total"] ?? ""),
        ];
    }

    private function normalizeLookupResult(array $result): array
    {
        return [
            "item_id" => (string) ($result["itm_selectid"] ?? $result["item_id"] ?? ""),
            "cat_id" => (string) ($result["itm_selcatid"] ?? $result["cat_id"] ?? ""),
            "name" => (string) ($result["itm_selctnme"] ?? $result["name"] ?? ""),
            "type" => (string) ($result["itm_itmstype"] ?? $result["type"] ?? ""),
            "cost_price" => (string) ($result["itm_costprce"] ?? $result["cost_price"] ?? ""),
            "sell_price" => (string) ($result["itm_sellpris"] ?? $result["sell_price"] ?? ""),
            "low_price" => (string) ($result["itm_lowprise"] ?? $result["low_price"] ?? ""),
            "other_price" => (string) ($result["itm_oterprse"] ?? $result["other_price"] ?? ""),
            "warranty" => (string) ($result["itm_warntyad"] ?? $result["warranty"] ?? ""),
            "stock_total" => (string) ($result["itm_stktotal"] ?? $result["stock_total"] ?? ""),
            "code" => (string) ($result["itm_imeicode"] ?? $result["code"] ?? ""),
            "row_id" => (string) ($result["row_ids_data"] ?? $result["row_id"] ?? ""),
            "supplier_id" => (string) ($result["itm_suply_id"] ?? $result["supplier_id"] ?? ""),
        ];
    }
}
