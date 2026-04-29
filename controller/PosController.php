<?php

declare(strict_types=1);

class PosController
{
    private const SLOT_IDS = [1, 2, 3];

    public function index(Request $request): void
    {
        $categoryModel = new ProductCategory();
        $activeSlot = $this->activeSlot();

        View::make("pos/index", [
            "title" => "Point Of Sale",
            "auth" => auth_user(),
            "categories" => $categoryModel->allOrdered(),
            "cart" => $this->cart($activeSlot),
            "activeSlot" => $activeSlot,
            "slotStates" => $this->slotStates(),
            "flash" => $_SESSION["flash"] ?? null,
        ]);

        unset($_SESSION["flash"]);
    }

    public function switchSlot(Request $request, string $slot): void
    {
        $slotId = $this->normalizeSlot($slot);

        if ($slotId === null) {
            http_response_code(404);
            View::make("errors/404", ["title" => "Slot Not Found"]);
            return;
        }

        $this->openSlot($slotId);
        $this->setActiveSlot($slotId);
        $_SESSION["flash"] = ["type" => "success", "message" => "Bill slot " . $slotId . " is now active."];
        redirect("/pos");
    }

    public function clearSlot(Request $request, string $slot): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $slotId = $this->normalizeSlot($slot);

        if ($slotId === null) {
            http_response_code(404);
            View::make("errors/404", ["title" => "Slot Not Found"]);
            return;
        }

        $mode = (string) $request->input("mode", "clear");
        $this->resetSlot($slotId);

        if ($slotId > 1 && $mode === "close") {
            $state = $this->slotStore();
            $state["open"][$slotId] = false;
            $this->storeSlotState($state);

            if ($this->activeSlot() === $slotId) {
                $this->setActiveSlot(1);
            }

            $_SESSION["flash"] = ["type" => "success", "message" => "Bill slot " . $slotId . " was closed."];
        } else {
            $_SESSION["flash"] = ["type" => "success", "message" => "Bill slot " . $slotId . " was cleared."];
        }

        redirect("/pos");
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

    public function salesPersonLookup(Request $request, string $id): void
    {
        $userModel = new User();
        $user = $userModel->findActiveById((int) $id);

        json_response([
            "found" => $user !== null,
            "seller" => $user === null ? null : [
                "id" => (int) $user["myid"],
                "name" => (string) ($user["visibledata"] ?? $user["ankaya"] ?? ""),
                "username" => (string) ($user["ankaya"] ?? ""),
            ],
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
        $activeSlot = $this->activeSlot();
        $cart = $this->cart($activeSlot);
        $cart["customer"] = [
            "id" => $customerId,
            "name" => $customerName === "" ? "Cash Customer" : $customerName,
        ];
        $this->storeCart($activeSlot, $cart);

        $_SESSION["flash"] = ["type" => "success", "message" => "Customer updated for the current bill."];
        redirect("/pos");
    }

    public function selectSalesPerson(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $sellerId = (int) $request->input("seller_id", 0);
        $userModel = new User();
        $seller = $sellerId > 0 ? $userModel->findActiveById($sellerId) : null;

        if ($sellerId > 0 && $seller === null) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Sale person not found or inactive."];
            redirect("/pos");
        }

        $activeSlot = $this->activeSlot();
        $cart = $this->cart($activeSlot);
        $auth = auth_user() ?? [];

        if ($seller === null) {
            $cart["seller"] = [
                "id" => (int) ($auth["user_id"] ?? 0),
                "name" => (string) ($auth["display_name"] ?? $auth["username"] ?? "User"),
            ];
        } else {
            $cart["seller"] = [
                "id" => (int) $seller["myid"],
                "name" => (string) ($seller["visibledata"] ?? $seller["ankaya"] ?? ""),
            ];
        }

        $this->storeCart($activeSlot, $cart);
        $_SESSION["flash"] = ["type" => "success", "message" => "Sale person updated for the current bill."];
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

        $activeSlot = $this->activeSlot();
        $cart = $this->cart($activeSlot);
        $cart["lines"][] = $this->buildCartLine($lookup, $qty, $salePrice, $discount, $warranty);
        $this->storeCart($activeSlot, $cart);

        $_SESSION["flash"] = ["type" => "success", "message" => "Item added to the current bill."];
        redirect("/pos");
    }

    public function addBulkImeiItems(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $lookup = $this->normalizeLookupResult(json_decode((string) $request->input("lookup_payload", "{}"), true) ?: []);
        $itemId = (int) ($lookup["item_id"] ?? 0);
        $categoryId = (int) ($lookup["cat_id"] ?? 0);
        $type = (int) ($lookup["type"] ?? 0);

        if ($itemId < 1 || $categoryId < 1 || $type !== 2) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Select a valid IMEI-controlled item before bulk add."];
            redirect("/pos");
        }

        $requestedQty = max(1, (int) $request->input("qty", 1));
        $salePrice = (float) $request->input("sale_price", $lookup["sell_price"] ?? 0);
        $discount = max(0, (float) $request->input("discount", 0));
        $warranty = trim((string) $request->input("warranty", (string) ($lookup["warranty"] ?? "")));
        $input = (string) $request->input("imei_bulk_input", "");
        $imeis = $this->parseImeis($input);

        if ($imeis === []) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Enter at least one IMEI for bulk add."];
            redirect("/pos");
        }

        if (count($imeis) !== $requestedQty) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Bulk IMEI count must match the requested quantity."];
            redirect("/pos");
        }

        $auth = auth_user() ?? [];
        $itemModel = new Item();
        $matches = $itemModel->imeiBulkMatches($itemId, $categoryId, (int) ($auth["shop_id"] ?? 0), $imeis);
        $alreadyStaged = $this->stagedImeiCodes();
        $cart = $this->cart($this->activeSlot());

        foreach ($imeis as $imei) {
            if (in_array($imei, $alreadyStaged, true)) {
                $_SESSION["flash"] = ["type" => "error", "message" => $imei . " is already staged in another bill slot."];
                redirect("/pos");
            }

            if (!isset($matches[$imei])) {
                $_SESSION["flash"] = ["type" => "error", "message" => $imei . " was not matched to available stock for the selected item."];
                redirect("/pos");
            }
        }

        foreach ($imeis as $imei) {
            $row = $matches[$imei];
            $lineLookup = [
                "item_id" => (string) $itemId,
                "cat_id" => (string) $categoryId,
                "type" => "2",
                "name" => (string) ($row["item_name"] ?? $lookup["name"] ?? ""),
                "code" => (string) $imei,
                "cost_price" => (string) ($row["item_cost_price"] ?? $lookup["cost_price"] ?? ""),
                "sell_price" => (string) ($row["item_sell_price"] ?? $lookup["sell_price"] ?? ""),
                "warranty" => $warranty === "" ? (string) ($lookup["warranty"] ?? "") : $warranty,
                "row_id" => (string) ($row["item_stock_id_imei"] ?? ""),
                "supplier_id" => (string) ($row["supplier_id"] ?? ""),
                "stock_total" => (string) ($lookup["stock_total"] ?? ""),
            ];

            $cart["lines"][] = $this->buildCartLine($lineLookup, 1, $salePrice, $discount, $warranty);
        }

        $this->storeCart($this->activeSlot(), $cart);
        $_SESSION["flash"] = ["type" => "success", "message" => count($imeis) . " IMEI item(s) added to the current bill."];
        redirect("/pos");
    }

    public function updateLine(Request $request, string $index): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $lineIndex = (int) $index;
        $activeSlot = $this->activeSlot();
        $cart = $this->cart($activeSlot);

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

        $this->storeCart($activeSlot, $cart);
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
        $activeSlot = $this->activeSlot();
        $cart = $this->cart($activeSlot);

        if (isset($cart["lines"][$lineIndex])) {
            array_splice($cart["lines"], $lineIndex, 1);
            $this->storeCart($activeSlot, $cart);
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

        $this->resetSlot($this->activeSlot());
        $_SESSION["flash"] = ["type" => "success", "message" => "Current bill cleared."];
        redirect("/pos");
    }

    public function updatePayment(Request $request): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            $_SESSION["flash"] = ["type" => "error", "message" => "Invalid CSRF token."];
            redirect("/pos");
        }

        $activeSlot = $this->activeSlot();
        $cart = $this->cart($activeSlot);
        $cart["payment"] = [
            "method" => (string) $request->input("method", "cash"),
            "cash_amount" => max(0, (float) $request->input("cash_amount", 0)),
            "card_amount" => max(0, (float) $request->input("card_amount", 0)),
            "card_number" => trim((string) $request->input("card_number", "")),
        ];
        $this->storeCart($activeSlot, $cart);

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
        $activeSlot = $this->activeSlot();
        $cart = $this->cart($activeSlot);

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
            $seller = $cart["seller"] ?? [
                "id" => (int) ($auth["user_id"] ?? 0),
                "name" => (string) ($auth["display_name"] ?? $auth["username"] ?? "User"),
            ];
            $billNumber = $saleModel->checkout([
                "shop_id" => (int) ($auth["shop_id"] ?? 0),
                "user_id" => (int) ($auth["user_id"] ?? 0),
                "seller_id" => (int) ($seller["id"] ?? 0),
                "seller_name" => (string) ($seller["name"] ?? $auth["display_name"] ?? $auth["username"] ?? "User"),
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

        $this->resetSlot($activeSlot);

        if ($activeSlot > 1) {
            $state = $this->slotStore();
            $state["open"][$activeSlot] = false;
            $this->storeSlotState($state);
            $this->setActiveSlot(1);
        }

        redirect("/pos/receipts/" . $billNumber);
    }

    public function receipt(Request $request, string $billNumber): void
    {
        $context = $this->receiptContext($billNumber);

        if ($context === null) {
            $this->receiptNotFound();
            return;
        }

        View::make("pos/receipt", [
            "title" => "POS Receipt",
            "auth" => auth_user(),
            "bill" => $context["bill"],
            "lines" => $context["lines"],
            "customer" => $context["customer"],
            "cashier" => $context["cashier"],
            "shop" => $context["shop"],
        ]);
    }

    public function printReceipt(Request $request, string $billNumber): void
    {
        $context = $this->receiptContext($billNumber);

        if ($context === null) {
            $this->receiptNotFound();
            return;
        }

        View::make("pos/receipt_print", [
            "title" => "Print Receipt",
            "bill" => $context["bill"],
            "lines" => $context["lines"],
            "customer" => $context["customer"],
            "cashier" => $context["cashier"],
            "shop" => $context["shop"],
        ], "print");
    }

    public function barcodeLabels(Request $request, string $billNumber): void
    {
        $context = $this->receiptContext($billNumber);

        if ($context === null) {
            $this->receiptNotFound();
            return;
        }

        $saleModel = new PosSale();

        View::make("pos/barcodes", [
            "title" => "Barcode Labels",
            "auth" => auth_user(),
            "bill" => $context["bill"],
            "shop" => $context["shop"],
            "labelLines" => $saleModel->barcodeLabels($billNumber),
        ]);
    }

    public function printBarcodeLabels(Request $request, string $billNumber): void
    {
        if (!verify_csrf((string) $request->input("_token"))) {
            http_response_code(419);
            exit("Invalid CSRF token.");
        }

        $context = $this->receiptContext($billNumber);

        if ($context === null) {
            $this->receiptNotFound();
            return;
        }

        $itemNames = (array) $request->input("item_name", []);
        $codes = (array) $request->input("code", []);
        $supplierIds = (array) $request->input("supplier_id", []);
        $printCounts = (array) $request->input("print_count", []);
        $labels = [];

        foreach ($codes as $index => $code) {
            $count = max(0, (int) ($printCounts[$index] ?? 0));

            if ($count === 0 || trim((string) $code) === "") {
                continue;
            }

            $labels[] = [
                "item_name" => trim((string) ($itemNames[$index] ?? "")),
                "code" => trim((string) $code),
                "supplier_id" => trim((string) ($supplierIds[$index] ?? "")),
                "count" => $count,
            ];
        }

        View::make("pos/barcodes_print", [
            "title" => "Print Barcode Labels",
            "bill" => $context["bill"],
            "shop" => $context["shop"],
            "labels" => $labels,
        ], "print");
    }

    private function cart(?int $slotId = null): array
    {
        $slot = $slotId ?? $this->activeSlot();
        $state = $this->slotStore();
        $cart = $state["carts"][$slot] ?? null;

        if (!is_array($cart)) {
            $cart = $this->emptyCart();
        }

        $cart["summary"] = $this->summary($cart["lines"], $cart["payment"]);

        return $cart;
    }

    private function storeCart(int $slotId, array $cart): void
    {
        $cart["summary"] = $this->summary($cart["lines"] ?? [], $cart["payment"] ?? []);
        $state = $this->slotStore();
        $state["open"][$slotId] = true;
        $state["carts"][$slotId] = $cart;
        $this->storeSlotState($state);
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

    private function parseImeis(string $input): array
    {
        $sanitized = preg_replace('/[^0-9A-Za-z]/', '', $input) ?? '';

        if ($sanitized !== '' && strlen($sanitized) % 15 === 0 && strlen($sanitized) > 15) {
            return array_values(array_filter(str_split($sanitized, 15)));
        }

        $parts = preg_split('/[\s,;|]+/', trim($input)) ?: [];

        return array_values(array_filter(array_map(
            static fn (mixed $part): string => trim((string) $part),
            $parts,
        )));
    }

    private function stagedImeiCodes(): array
    {
        $state = $this->slotStore();
        $codes = [];

        foreach (self::SLOT_IDS as $slotId) {
            $lines = $state["carts"][$slotId]["lines"] ?? [];
            foreach ($lines as $line) {
                if ((string) ($line["type"] ?? "") === "2" && trim((string) ($line["code"] ?? "")) !== "") {
                    $codes[] = (string) $line["code"];
                }
            }
        }

        return array_values(array_unique($codes));
    }

    private function activeSlot(): int
    {
        $state = $this->slotStore();
        $slot = (int) ($state["active"] ?? 1);

        return in_array($slot, self::SLOT_IDS, true) ? $slot : 1;
    }

    private function setActiveSlot(int $slotId): void
    {
        $state = $this->slotStore();
        $state["active"] = $slotId;
        $this->storeSlotState($state);
    }

    private function openSlot(int $slotId): void
    {
        $state = $this->slotStore();
        $state["open"][$slotId] = true;

        if (!isset($state["carts"][$slotId]) || !is_array($state["carts"][$slotId])) {
            $state["carts"][$slotId] = $this->emptyCart();
        }

        $this->storeSlotState($state);
    }

    private function resetSlot(int $slotId): void
    {
        $state = $this->slotStore();
        $state["carts"][$slotId] = $this->emptyCart();
        $this->storeSlotState($state);
    }

    private function slotStates(): array
    {
        $state = $this->slotStore();
        $output = [];

        foreach (self::SLOT_IDS as $slotId) {
            $cart = $this->cart($slotId);
            $output[] = [
                "slot" => $slotId,
                "active" => $this->activeSlot() === $slotId,
                "open" => (bool) ($state["open"][$slotId] ?? false),
                "customer_name" => (string) ($cart["customer"]["name"] ?? "Cash Customer"),
                "item_count" => count($cart["lines"] ?? []),
                "total" => (float) ($cart["summary"]["total"] ?? 0),
            ];
        }

        return $output;
    }

    private function slotStore(): array
    {
        $state = $_SESSION["pos_slots"] ?? null;

        if (!is_array($state)) {
            $state = [
                "active" => 1,
                "open" => [
                    1 => true,
                    2 => false,
                    3 => false,
                ],
                "carts" => [
                    1 => $this->emptyCart(),
                    2 => $this->emptyCart(),
                    3 => $this->emptyCart(),
                ],
            ];
            $_SESSION["pos_slots"] = $state;
        }

        return $state;
    }

    private function storeSlotState(array $state): void
    {
        $_SESSION["pos_slots"] = $state;
    }

    private function emptyCart(): array
    {
        $auth = auth_user() ?? [];

        return [
            "customer" => ["id" => 0, "name" => "Cash Customer"],
            "seller" => [
                "id" => (int) ($auth["user_id"] ?? 0),
                "name" => (string) ($auth["display_name"] ?? $auth["username"] ?? "User"),
            ],
            "lines" => [],
            "payment" => [
                "method" => "cash",
                "cash_amount" => 0.0,
                "card_amount" => 0.0,
                "card_number" => "",
            ],
        ];
    }

    private function normalizeSlot(string|int $slot): ?int
    {
        $slotId = (int) $slot;
        return in_array($slotId, self::SLOT_IDS, true) ? $slotId : null;
    }

    private function receiptContext(string $billNumber): ?array
    {
        $saleModel = new PosSale();
        $shopModel = new Shop();
        $customerModel = new Customer();
        $userModel = new User();
        $receipt = $saleModel->receipt($billNumber);
        $auth = auth_user() ?? [];

        if ($receipt === null) {
            return null;
        }

        $bill = $receipt["bill"];
        $billShopId = (int) ($bill["billed_shop"] ?? 0);

        if ((int) ($auth["shop_id"] ?? 0) !== $billShopId) {
            return null;
        }

        return [
            "bill" => $bill,
            "lines" => $receipt["lines"],
            "customer" => (int) ($bill["customer_id"] ?? 0) > 0
                ? $customerModel->findById((int) $bill["customer_id"])
                : null,
            "cashier" => $userModel->findById((int) ($bill["operator"] ?? 0)),
            "shop" => $shopModel->findByShopId($billShopId),
        ];
    }

    private function receiptNotFound(): void
    {
        http_response_code(404);
        View::make("errors/404", ["title" => "Receipt Not Found"]);
    }
}
