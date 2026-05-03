<?php

declare(strict_types=1);

class Grn extends Model
{
    protected $table = "shop_grnmain";

    public function draft(array $auth): array
    {
        return $_SESSION[$this->draftSessionKey($auth)] ?? [
            "supplier_id" => 0,
            "supplier_name" => "",
            "supplier_address" => "",
            "supplier_mobile" => "",
            "invoice_number" => "",
            "lines" => [],
        ];
    }

    public function updateDraftHeader(array $auth, array $data): void
    {
        $draft = $this->draft($auth);
        $supplierId = (int) ($data["supplier_id"] ?? 0);
        $supplierModel = new Supplier();
        $supplier = $supplierModel->findById($supplierId);

        if ($supplier === null) {
            throw new RuntimeException("Supplier not found.");
        }

        $draft["supplier_id"] = (int) ($supplier["supplierid"] ?? 0);
        $draft["supplier_name"] = (string) ($supplier["supplier_name"] ?? "");
        $draft["supplier_address"] = (string) ($supplier["supplier_address"] ?? "");
        $draft["supplier_mobile"] = (string) ($supplier["supplier_mobile"] ?? "");
        $draft["invoice_number"] = trim((string) ($data["invoice_number"] ?? ""));

        $_SESSION[$this->draftSessionKey($auth)] = $draft;
    }

    public function addDraftLine(array $auth, array $data): void
    {
        $draft = $this->draft($auth);
        $itemModel = new Item();
        $categoryModel = new ProductCategory();
        $itemName = trim((string) ($data["item_name"] ?? ""));
        $item = $itemModel->findByName($itemName);

        if ($item === null) {
            throw new RuntimeException("Selected item was not found.");
        }

        $categoryId = (int) ($item["item_cat"] ?? 0);
        $category = $categoryModel->findByName(trim((string) ($data["category_name"] ?? "")));
        if ($category !== null && (int) ($category["catid"] ?? 0) !== $categoryId) {
            throw new RuntimeException("Selected item does not belong to the chosen category.");
        }

        $usedType = (int) ($item["used_type"] ?? 0);
        $qty = (int) ($data["qty"] ?? 0);
        $cost = (float) ($data["cost_price"] ?? 0);
        $sell = (float) ($data["sell_price"] ?? 0);
        $low = (float) ($data["low_price"] ?? 0);
        $other = (float) ($data["other_price"] ?? 0);
        $shopId = (int) ($data["stock_shop_id"] ?? 0);
        $authShopId = (int) ($auth["shop_id"] ?? 0);

        if ($authShopId > 0) {
            $shopId = $authShopId;
        }

        $shopModel = new Shop();
        $shop = $shopModel->findByShopId($shopId);

        if ($shop === null) {
            throw new RuntimeException("Stock adding shop was not found.");
        }

        if ($qty < 1 || $cost < 0 || $sell < 0 || $low < 0 || $other < 0) {
            throw new RuntimeException("Quantity and prices must be valid values.");
        }

        $imei = trim((string) ($data["imei_no"] ?? ""));
        if ($usedType === 2) {
            if ($qty !== 1) {
                throw new RuntimeException("IMEI items must be added one by one in this GRN slice.");
            }

            if ($imei === "") {
                throw new RuntimeException("IMEI number is required for IMEI-controlled items.");
            }

            if ($this->imeiExistsInSystem($imei)) {
                throw new RuntimeException("That IMEI is already registered in the system.");
            }

            foreach ($draft["lines"] as $line) {
                if ((string) ($line["imei_no"] ?? "") === $imei) {
                    throw new RuntimeException("That IMEI is already staged in the current GRN.");
                }
            }
        } else {
            $imei = "";
        }

        $draft["lines"][] = [
            "object_type" => $usedType,
            "item_category" => (string) ($category["catname"] ?? ""),
            "item_category_id" => $categoryId,
            "item_name" => (string) ($item["item_name"] ?? ""),
            "item_id" => (int) ($item["item_id"] ?? 0),
            "imei_no" => $imei,
            "item_color" => trim((string) ($data["item_color"] ?? "")),
            "item_qty" => $qty,
            "item_costpri" => $cost,
            "item_sellpri" => $sell,
            "item_free" => !empty($data["item_free"]) ? 1 : 0,
            "item_lowpri" => $low,
            "item_otherpri" => $other,
            "stock_shop_id" => $shopId,
            "stock_shop_name" => (string) ($shop["shop_info_name"] ?? $shop["shopname"] ?? ""),
            "Warranty_Span" => max(0, (int) ($data["warranty_span"] ?? 0)),
            "Warranty_Type" => trim((string) ($data["warranty_type"] ?? "")),
            "sub_total" => $qty * $cost,
        ];

        $_SESSION[$this->draftSessionKey($auth)] = $draft;
    }

    public function removeDraftLine(array $auth, int $index): void
    {
        $draft = $this->draft($auth);

        if (!isset($draft["lines"][$index])) {
            throw new RuntimeException("Draft line was not found.");
        }

        array_splice($draft["lines"], $index, 1);
        $_SESSION[$this->draftSessionKey($auth)] = $draft;
    }

    public function clearDraft(array $auth): void
    {
        unset($_SESSION[$this->draftSessionKey($auth)]);
    }

    public function itemDraftDetailsByName(string $itemName, int $authShopId): ?array
    {
        $itemModel = new Item();
        $item = $itemModel->findByName(trim($itemName));

        if ($item === null) {
            return null;
        }

        $categoryModel = new ProductCategory();
        $category = $categoryModel->findByName((string) $this->categoryNameById((int) ($item["item_cat"] ?? 0)));
        $priceStmt = $this->db->prepare(
            "SELECT *
             FROM shop_grnitem
             WHERE item_name = :item_name
               AND (:shop_id = 0 OR stock_shop = :shop_id)
             ORDER BY recordid DESC
             LIMIT 1",
        );
        $priceStmt->execute([
            "item_name" => (string) ($item["item_name"] ?? ""),
            "shop_id" => $authShopId,
        ]);
        $latest = $priceStmt->fetch() ?: [];

        return [
            "found" => true,
            "item_name" => (string) ($item["item_name"] ?? ""),
            "item_id" => (int) ($item["item_id"] ?? 0),
            "category_id" => (int) ($item["item_cat"] ?? 0),
            "category_name" => (string) ($category["catname"] ?? $this->categoryNameById((int) ($item["item_cat"] ?? 0))),
            "used_type" => (int) ($item["used_type"] ?? 0),
            "cost_price" => (float) ($latest["item_costpri"] ?? 0),
            "sell_price" => (float) ($latest["item_sellpri"] ?? 0),
            "low_price" => (float) ($latest["item_lowpri"] ?? 0),
            "other_price" => (float) ($latest["item_otherpri"] ?? 0),
        ];
    }

    public function finalizeDraft(array $auth, array $payment): string
    {
        $draft = $this->draft($auth);
        $lines = $draft["lines"] ?? [];

        if ((int) ($draft["supplier_id"] ?? 0) < 1) {
            throw new RuntimeException("Select the supplier correctly before submitting the GRN.");
        }

        if ($lines === []) {
            throw new RuntimeException("Add GRN records before submitting.");
        }

        $cashAmount = max(0, (float) ($payment["cash_amount"] ?? 0));
        $chequeAmount = max(0, (float) ($payment["cheque_amount"] ?? 0));
        $discountAmount = max(0, (float) ($payment["discount_amount"] ?? 0));
        $discountPercent = max(0, (float) ($payment["discount_percent"] ?? 0));
        $chequeNumber = trim((string) ($payment["cheque_number"] ?? ""));
        $chequeDate = trim((string) ($payment["cheque_date"] ?? ""));
        $chequeReminder = (int) ($payment["cheque_reminder"] ?? 0);
        $chequeReminderDate = trim((string) ($payment["cheque_reminder_date"] ?? ""));

        $subTotal = array_reduce($lines, static function (float $carry, array $line): float {
            return $carry + (float) ($line["sub_total"] ?? 0);
        }, 0.0);

        if ($discountAmount > 0 && $discountPercent > 0) {
            throw new RuntimeException("Use either discount amount or discount percent, not both.");
        }

        if ($discountPercent > 0) {
            $discountAmount = round($subTotal * ($discountPercent / 100), 2);
        }

        $finalAmount = round($subTotal - $discountAmount, 2);
        if ($finalAmount < 0) {
            throw new RuntimeException("Discount cannot reduce the GRN below zero.");
        }

        if (($cashAmount + $chequeAmount) > $finalAmount) {
            throw new RuntimeException("Cash and cheque payments cannot exceed the total GRN value.");
        }

        if ($chequeAmount > 0 && ($chequeNumber === "" || $chequeDate === "")) {
            throw new RuntimeException("Cheque number and cheque date are required for cheque payments.");
        }

        $userId = (int) ($auth["user_id"] ?? 0);
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $recordTime = date("Y-m-d H:i:s");
        $grnMonth = date("Ym");
        $fixedYear = substr($grnMonth, 2);
        $barcodePrefix = substr($grnMonth, 3);

        $this->db->beginTransaction();

        try {
            $seqStmt = $this->db->prepare(
                "SELECT COALESCE(MAX(grn_seq), 0)
                 FROM shop_grnmain
                 WHERE grn_month = :grn_month
                 FOR UPDATE",
            );
            $seqStmt->execute(["grn_month" => $grnMonth]);
            $grnSeq = ((int) $seqStmt->fetchColumn()) + 1;
            $grnRefNo = "GRN" . $grnMonth . str_pad((string) $grnSeq, 3, "0", STR_PAD_LEFT) . random_int(10, 99);

            $insertHead = $this->db->prepare(
                "INSERT INTO shop_grnmain
                 (grn_month, grn_seq, operator, shop_number, grn_refno, amount, discount_mny, discount_precent, final_amount, invoice_no, invoice_date, supply_id, suppler_name, supplier_address, supplier_mobile, cash_amount, chq_amount, chq_number, chq_date, chq_reminder, chq_reminder_date, operation_time)
                 VALUES
                 (:grn_month, :grn_seq, :operator, :shop_number, :grn_refno, :amount, :discount_mny, :discount_precent, :final_amount, :invoice_no, CURDATE(), :supply_id, :suppler_name, :supplier_address, :supplier_mobile, :cash_amount, :chq_amount, :chq_number, :chq_date, :chq_reminder, :chq_reminder_date, :operation_time)",
            );
            $insertHead->execute([
                "grn_month" => $grnMonth,
                "grn_seq" => $grnSeq,
                "operator" => $userId,
                "shop_number" => $shopId,
                "grn_refno" => $grnRefNo,
                "amount" => $subTotal,
                "discount_mny" => $discountAmount,
                "discount_precent" => $discountPercent,
                "final_amount" => $finalAmount,
                "invoice_no" => (string) ($draft["invoice_number"] ?? ""),
                "supply_id" => (int) ($draft["supplier_id"] ?? 0),
                "suppler_name" => (string) ($draft["supplier_name"] ?? ""),
                "supplier_address" => (string) ($draft["supplier_address"] ?? ""),
                "supplier_mobile" => (string) ($draft["supplier_mobile"] ?? ""),
                "cash_amount" => $cashAmount,
                "chq_amount" => $chequeAmount,
                "chq_number" => $chequeNumber,
                "chq_date" => $chequeDate !== "" ? $chequeDate : null,
                "chq_reminder" => $chequeReminder,
                "chq_reminder_date" => $chequeReminderDate !== "" ? $chequeReminderDate : null,
                "operation_time" => $recordTime,
            ]);

            $stockSeqStmt = $this->db->prepare(
                "SELECT COALESCE(MAX(gen_seq), 0)
                 FROM shop_stock_item
                 WHERE valied_month = :grn_month
                 FOR UPDATE",
            );
            $stockSeqStmt->execute(["grn_month" => $grnMonth]);
            $barcodeSeq = (int) $stockSeqStmt->fetchColumn();

            foreach ($lines as $line) {
                $insertLine = $this->db->prepare(
                    "INSERT INTO shop_grnitem
                     (grn_refno, object_type, item_category, item_name, imei_no, item_color, item_qty, item_costpri, item_sellpri, item_free, item_lowpri, item_otherpri, warrenty_s, warrenty_t, stock_shop)
                     VALUES
                     (:grn_refno, :object_type, :item_category, :item_name, :imei_no, :item_color, :item_qty, :item_costpri, :item_sellpri, :item_free, :item_lowpri, :item_otherpri, :warrenty_s, :warrenty_t, :stock_shop)",
                );
                $insertLine->execute([
                    "grn_refno" => $grnRefNo,
                    "object_type" => (int) ($line["object_type"] ?? 0),
                    "item_category" => (string) ($line["item_category"] ?? ""),
                    "item_name" => (string) ($line["item_name"] ?? ""),
                    "imei_no" => (string) ($line["imei_no"] ?? ""),
                    "item_color" => (string) ($line["item_color"] ?? ""),
                    "item_qty" => (int) ($line["item_qty"] ?? 0),
                    "item_costpri" => (float) ($line["item_costpri"] ?? 0),
                    "item_sellpri" => (float) ($line["item_sellpri"] ?? 0),
                    "item_free" => (int) ($line["item_free"] ?? 0),
                    "item_lowpri" => (float) ($line["item_lowpri"] ?? 0),
                    "item_otherpri" => (float) ($line["item_otherpri"] ?? 0),
                    "warrenty_s" => (int) ($line["Warranty_Span"] ?? 0),
                    "warrenty_t" => (string) ($line["Warranty_Type"] ?? ""),
                    "stock_shop" => (int) ($line["stock_shop_id"] ?? 0),
                ]);

                $type = (int) ($line["object_type"] ?? 0);
                if ($type === 1) {
                    $barcodeSeq++;
                    $this->insertBarcodeStock($grnRefNo, $grnMonth, $fixedYear, $barcodePrefix, $barcodeSeq, (int) ($draft["supplier_id"] ?? 0), $line, $recordTime);
                } elseif ($type === 2) {
                    $this->insertImeiStock($grnRefNo, (int) ($draft["supplier_id"] ?? 0), $line, $recordTime);
                } else {
                    $this->upsertRechargeStock($line, $recordTime);
                }
            }

            $this->insertSupplierLedger($draft, $userId, $finalAmount, $cashAmount, $chequeAmount, $grnRefNo, $recordTime);
            $this->insertGrnPayment($draft, $grnRefNo, $shopId, $userId, $finalAmount, $cashAmount, $chequeAmount, [
                "cheque_number" => $chequeNumber,
                "cheque_date" => $chequeDate,
                "cheque_reminder" => $chequeReminder,
                "cheque_reminder_date" => $chequeReminderDate,
            ], $recordTime);

            $this->db->commit();
            $this->clearDraft($auth);

            return $grnRefNo;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function search(array $filters, int $authShopId): array
    {
        $grnId = trim((string) ($filters["grn_id"] ?? ""));
        $supplier = trim((string) ($filters["supplier"] ?? ""));
        $itemName = trim((string) ($filters["item_name"] ?? ""));
        $imei = trim((string) ($filters["imei"] ?? ""));
        $shopId = (int) ($filters["shop_id"] ?? -1);
        $startDate = trim((string) ($filters["start_date"] ?? ""));
        $endDate = trim((string) ($filters["end_date"] ?? ""));

        $shopScopeId = $authShopId > 0 ? $authShopId : $shopId;
        if ($authShopId > 0) {
            $shopId = $authShopId;
        }

        $useToday = $grnId === ""
            && $supplier === ""
            && $itemName === ""
            && $imei === ""
            && $startDate === ""
            && $endDate === ""
            && $shopId < 0;

        if ($useToday) {
            $startDate = date("Y-m-d");
            $endDate = date("Y-m-d");
        } elseif ($startDate === "" && $endDate !== "") {
            $startDate = $endDate;
        } elseif ($startDate !== "" && $endDate === "") {
            $endDate = $startDate;
        }

        if ($itemName !== "" || $imei !== "") {
            $refs = $this->findGrnReferencesByItems($itemName, $imei);
            if ($refs === []) {
                return [];
            }

            $headers = $this->loadHeadersByReferences($refs, $grnId, $supplier, $shopScopeId, $startDate, $endDate);
        } else {
            $headers = $this->loadHeaders($grnId, $supplier, $shopScopeId, $startDate, $endDate);
        }

        if ($headers === []) {
            return [];
        }

        $this->attachItems($headers);
        return $headers;
    }

    private function loadHeaders(string $grnId, string $supplier, int $shopId, string $startDate, string $endDate): array
    {
        $sql = "SELECT main.*,
                       user.visibledata AS operator_name,
                       user.ankaya AS operator_username,
                       shop.shop_info_name AS grn_shop_name
                FROM shop_grnmain AS main
                LEFT JOIN sys_user AS user
                  ON user.myid = main.operator
                LEFT JOIN sys_shop AS shop
                  ON shop.shopid = main.shop_number
                WHERE 1 = 1";
        $params = [];

        if ($grnId !== "") {
            $sql .= " AND main.grn_refno LIKE :grn_id";
            $params["grn_id"] = "%" . $grnId . "%";
        }

        if ($supplier !== "") {
            $sql .= " AND main.suppler_name LIKE :supplier";
            $params["supplier"] = "%" . $supplier . "%";
        }

        if ($shopId > 0) {
            $sql .= " AND main.shop_number = :shop_id";
            $params["shop_id"] = $shopId;
        }

        if ($startDate !== "") {
            $sql .= " AND date(main.operation_time) >= :start_date";
            $params["start_date"] = $startDate;
        }

        if ($endDate !== "") {
            $sql .= " AND date(main.operation_time) <= :end_date";
            $params["end_date"] = $endDate;
        }

        $sql .= " ORDER BY main.recordid DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function findGrnReferencesByItems(string $itemName, string $imei): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT grn_refno
             FROM shop_grnitem
             WHERE item_name LIKE :item_name
               AND imei_no LIKE :imei
             ORDER BY recordid DESC",
        );
        $stmt->execute([
            "item_name" => "%" . $itemName . "%",
            "imei" => "%" . $imei . "%",
        ]);

        return array_map(
            static fn (array $row): string => (string) ($row["grn_refno"] ?? ""),
            $stmt->fetchAll(),
        );
    }

    private function loadHeadersByReferences(array $references, string $grnId, string $supplier, int $shopId, string $startDate, string $endDate): array
    {
        $params = [];
        $placeholders = [];

        foreach ($references as $index => $reference) {
            $key = "ref_" . $index;
            $placeholders[] = ":" . $key;
            $params[$key] = $reference;
        }

        $sql = "SELECT main.*,
                       user.visibledata AS operator_name,
                       user.ankaya AS operator_username,
                       shop.shop_info_name AS grn_shop_name
                FROM shop_grnmain AS main
                LEFT JOIN sys_user AS user
                  ON user.myid = main.operator
                LEFT JOIN sys_shop AS shop
                  ON shop.shopid = main.shop_number
                WHERE main.grn_refno IN (" . implode(", ", $placeholders) . ")";

        if ($grnId !== "") {
            $sql .= " AND main.grn_refno LIKE :grn_id";
            $params["grn_id"] = "%" . $grnId . "%";
        }

        if ($supplier !== "") {
            $sql .= " AND main.suppler_name LIKE :supplier";
            $params["supplier"] = "%" . $supplier . "%";
        }

        if ($shopId > 0) {
            $sql .= " AND main.shop_number = :shop_id";
            $params["shop_id"] = $shopId;
        }

        if ($startDate !== "") {
            $sql .= " AND date(main.operation_time) >= :start_date";
            $params["start_date"] = $startDate;
        }

        if ($endDate !== "") {
            $sql .= " AND date(main.operation_time) <= :end_date";
            $params["end_date"] = $endDate;
        }

        $sql .= " ORDER BY main.recordid DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function attachItems(array &$headers): void
    {
        $refs = array_values(array_filter(array_map(
            static fn (array $header): string => (string) ($header["grn_refno"] ?? ""),
            $headers,
        )));

        if ($refs === []) {
            return;
        }

        $params = [];
        $placeholders = [];
        foreach ($refs as $index => $reference) {
            $key = "grn_" . $index;
            $placeholders[] = ":" . $key;
            $params[$key] = $reference;
        }

        $stmt = $this->db->prepare(
            "SELECT item.*,
                    shop.shop_info_name AS stock_shop_name
             FROM shop_grnitem AS item
             LEFT JOIN sys_shop AS shop
               ON shop.shopid = item.stock_shop
             WHERE item.grn_refno IN (" . implode(", ", $placeholders) . ")
             ORDER BY item.recordid ASC",
        );
        $stmt->execute($params);

        $grouped = [];
        foreach ($stmt->fetchAll() as $item) {
            $grouped[(string) ($item["grn_refno"] ?? "")][] = $item;
        }

        foreach ($headers as &$header) {
            $header["items"] = $grouped[(string) ($header["grn_refno"] ?? "")] ?? [];
        }
        unset($header);
    }

    private function imeiExistsInSystem(string $imei): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM shop_grnitem
             WHERE imei_no = :imei",
        );
        $stmt->execute(["imei" => $imei]);

        if ((int) $stmt->fetchColumn() > 0) {
            return true;
        }

        $stockStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM shop_stock_imei
             WHERE imei_no = :imei",
        );
        $stockStmt->execute(["imei" => $imei]);

        return (int) $stockStmt->fetchColumn() > 0;
    }

    private function categoryNameById(int $categoryId): string
    {
        $stmt = $this->db->prepare(
            "SELECT catname
             FROM prod_category
             WHERE catid = :catid
             LIMIT 1",
        );
        $stmt->execute(["catid" => $categoryId]);

        return (string) ($stmt->fetchColumn() ?: "");
    }

    private function draftSessionKey(array $auth): string
    {
        return "grn_draft_" . (int) ($auth["user_id"] ?? 0) . "_" . (int) ($auth["shop_id"] ?? 0);
    }

    private function insertBarcodeStock(string $grnRefNo, string $grnMonth, string $fixedYear, string $barcodePrefix, int $barcodeSeq, int $supplierId, array $line, string $recordTime): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO shop_stock_item
             (grn_refno, gen_refno, supplier_id, valied_month, fixed_yy, gen_seq, stock_added, stock_current, stock_status, item_cat, item_name, item_cat_id, item_name_id, item_color, item_cost_price, item_sell_price, item_free, item_low_price, item_other_price, warranty_span, warranty_type, stock_in_shop, barcode_print, stock_add_dt)
             VALUES
             (:grn_refno, :gen_refno, :supplier_id, :valied_month, :fixed_yy, :gen_seq, :stock_added, :stock_current, 1, :item_cat, :item_name, :item_cat_id, :item_name_id, :item_color, :item_cost_price, :item_sell_price, :item_free, :item_low_price, :item_other_price, :warranty_span, :warranty_type, :stock_in_shop, 0, :stock_add_dt)",
        );
        $stmt->execute([
            "grn_refno" => $grnRefNo,
            "gen_refno" => $barcodePrefix . str_pad((string) $barcodeSeq, 4, "0", STR_PAD_LEFT),
            "supplier_id" => $supplierId,
            "valied_month" => $grnMonth,
            "fixed_yy" => $fixedYear,
            "gen_seq" => $barcodeSeq,
            "stock_added" => (int) ($line["item_qty"] ?? 0),
            "stock_current" => (int) ($line["item_qty"] ?? 0),
            "item_cat" => (string) ($line["item_category"] ?? ""),
            "item_name" => (string) ($line["item_name"] ?? ""),
            "item_cat_id" => (int) ($line["item_category_id"] ?? 0),
            "item_name_id" => (int) ($line["item_id"] ?? 0),
            "item_color" => (string) ($line["item_color"] ?? ""),
            "item_cost_price" => (float) ($line["item_costpri"] ?? 0),
            "item_sell_price" => (float) ($line["item_sellpri"] ?? 0),
            "item_free" => (int) ($line["item_free"] ?? 0),
            "item_low_price" => (float) ($line["item_lowpri"] ?? 0),
            "item_other_price" => (float) ($line["item_otherpri"] ?? 0),
            "warranty_span" => (int) ($line["Warranty_Span"] ?? 0),
            "warranty_type" => (string) ($line["Warranty_Type"] ?? ""),
            "stock_in_shop" => (int) ($line["stock_shop_id"] ?? 0),
            "stock_add_dt" => $recordTime,
        ]);
    }

    private function insertImeiStock(string $grnRefNo, int $supplierId, array $line, string $recordTime): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO shop_stock_imei
             (grn_refno, supplier_id, stock_current, imei_no, item_cat, item_name, item_cat_id, item_name_id, item_color, item_cost_price, item_sell_price, item_free, item_low_price, item_other_price, warranty_span, warranty_type, stock_in_shop, stock_status, stock_add_dt)
             VALUES
             (:grn_refno, :supplier_id, 1, :imei_no, :item_cat, :item_name, :item_cat_id, :item_name_id, :item_color, :item_cost_price, :item_sell_price, :item_free, :item_low_price, :item_other_price, :warranty_span, :warranty_type, :stock_in_shop, 1, :stock_add_dt)",
        );
        $stmt->execute([
            "grn_refno" => $grnRefNo,
            "supplier_id" => $supplierId,
            "imei_no" => (string) ($line["imei_no"] ?? ""),
            "item_cat" => (string) ($line["item_category"] ?? ""),
            "item_name" => (string) ($line["item_name"] ?? ""),
            "item_cat_id" => (int) ($line["item_category_id"] ?? 0),
            "item_name_id" => (int) ($line["item_id"] ?? 0),
            "item_color" => (string) ($line["item_color"] ?? ""),
            "item_cost_price" => (float) ($line["item_costpri"] ?? 0),
            "item_sell_price" => (float) ($line["item_sellpri"] ?? 0),
            "item_free" => (int) ($line["item_free"] ?? 0),
            "item_low_price" => (float) ($line["item_lowpri"] ?? 0),
            "item_other_price" => (float) ($line["item_otherpri"] ?? 0),
            "warranty_span" => (int) ($line["Warranty_Span"] ?? 0),
            "warranty_type" => (string) ($line["Warranty_Type"] ?? ""),
            "stock_in_shop" => (int) ($line["stock_shop_id"] ?? 0),
            "stock_add_dt" => $recordTime,
        ]);
    }

    private function upsertRechargeStock(array $line, string $recordTime): void
    {
        $rechargeCard = new RechargeCard();
        $card = $rechargeCard->findByProductId((int) ($line["item_id"] ?? 0));

        if ($card === null) {
            throw new RuntimeException("Recharge card mapping was not found for " . (string) ($line["item_name"] ?? "") . ".");
        }

        $find = $this->db->prepare(
            "SELECT *
             FROM shop_rcv_stock
             WHERE card_id = :card_id
               AND stock_in_shop = :shop_id
             LIMIT 1
             FOR UPDATE",
        );
        $find->execute([
            "card_id" => (int) ($card["recordid"] ?? 0),
            "shop_id" => (int) ($line["stock_shop_id"] ?? 0),
        ]);
        $existing = $find->fetch();

        if ($existing !== false) {
            $update = $this->db->prepare(
                "UPDATE shop_rcv_stock
                 SET current_stock = current_stock + :qty,
                     cost_price = :cost_price,
                     sell_price = :sell_price,
                     low_price = :low_price,
                     other_price = :other_price,
                     last_upd = :last_upd
                 WHERE recordid = :recordid",
            );
            $update->execute([
                "qty" => (int) ($line["item_qty"] ?? 0),
                "cost_price" => (float) ($line["item_costpri"] ?? 0),
                "sell_price" => (float) ($line["item_sellpri"] ?? 0),
                "low_price" => (float) ($line["item_lowpri"] ?? 0),
                "other_price" => (float) ($line["item_otherpri"] ?? 0),
                "last_upd" => $recordTime,
                "recordid" => (int) ($existing["recordid"] ?? 0),
            ]);
            return;
        }

        $insert = $this->db->prepare(
            "INSERT INTO shop_rcv_stock
             (card_id, card_name, item_cat_id, item_name_id, current_stock, min_limit, stock_status, cost_price, sell_price, low_price, other_price, stock_in_shop, last_upd)
             VALUES
             (:card_id, :card_name, :item_cat_id, :item_name_id, :current_stock, 0, 1, :cost_price, :sell_price, :low_price, :other_price, :stock_in_shop, :last_upd)",
        );
        $insert->execute([
            "card_id" => (int) ($card["recordid"] ?? 0),
            "card_name" => (string) ($line["item_name"] ?? ""),
            "item_cat_id" => (int) ($line["item_category_id"] ?? 0),
            "item_name_id" => (int) ($line["item_id"] ?? 0),
            "current_stock" => (int) ($line["item_qty"] ?? 0),
            "cost_price" => (float) ($line["item_costpri"] ?? 0),
            "sell_price" => (float) ($line["item_sellpri"] ?? 0),
            "low_price" => (float) ($line["item_lowpri"] ?? 0),
            "other_price" => (float) ($line["item_otherpri"] ?? 0),
            "stock_in_shop" => (int) ($line["stock_shop_id"] ?? 0),
            "last_upd" => $recordTime,
        ]);
    }

    private function insertSupplierLedger(array $draft, int $userId, float $finalAmount, float $cashAmount, float $chequeAmount, string $grnRefNo, string $recordTime): void
    {
        $supplierId = (int) ($draft["supplier_id"] ?? 0);

        $grnStmt = $this->db->prepare(
            "INSERT INTO account_supplier
             (recordtime, supplier, operator, op_type, details, paytype, debit, credit)
             VALUES
             (:recordtime, :supplier, :operator, 7, :details, 1, 0, :credit)",
        );
        $grnStmt->execute([
            "recordtime" => $recordTime,
            "supplier" => $supplierId,
            "operator" => $userId,
            "details" => "GRN " . $grnRefNo . " amount need to be paid.",
            "credit" => $finalAmount,
        ]);

        if ($cashAmount > 0) {
            $cashStmt = $this->db->prepare(
                "INSERT INTO account_supplier
                 (recordtime, supplier, operator, op_type, details, paytype, debit, credit)
                 VALUES
                 (:recordtime, :supplier, :operator, 1, :details, 1, :debit, 0)",
            );
            $cashStmt->execute([
                "recordtime" => $recordTime,
                "supplier" => $supplierId,
                "operator" => $userId,
                "details" => "Cash Payment for GRN ID " . $grnRefNo,
                "debit" => $cashAmount,
            ]);
        }

        if ($chequeAmount > 0) {
            $chequeStmt = $this->db->prepare(
                "INSERT INTO account_supplier
                 (recordtime, supplier, operator, op_type, details, paytype, debit, credit)
                 VALUES
                 (:recordtime, :supplier, :operator, 2, :details, 2, :debit, 0)",
            );
            $chequeStmt->execute([
                "recordtime" => $recordTime,
                "supplier" => $supplierId,
                "operator" => $userId,
                "details" => "Cheque Payment for GRN ID " . $grnRefNo,
                "debit" => $chequeAmount,
            ]);
        }
    }

    private function insertGrnPayment(array $draft, string $grnRefNo, int $shopId, int $userId, float $finalAmount, float $cashAmount, float $chequeAmount, array $chequeData, string $recordTime): void
    {
        $dueAmount = round($finalAmount - $cashAmount - $chequeAmount, 2);
        $paidStatus = $dueAmount > 0 ? 1 : 0;
        $chequeNumber = trim((string) ($chequeData["cheque_number"] ?? ""));
        $chequeDate = trim((string) ($chequeData["cheque_date"] ?? ""));
        $chequeReminder = (int) ($chequeData["cheque_reminder"] ?? 0);
        $chequeReminderDate = trim((string) ($chequeData["cheque_reminder_date"] ?? ""));

        $payStmt = $this->db->prepare(
            "INSERT INTO shop_grn_pay
             (grn_refno, inv_number, shop_id, supply_id, payment_status, grn_final_amount, cash_pay_amount, chq_pay_amount, due_amount, complete_time, record_time)
             VALUES
             (:grn_refno, :inv_number, :shop_id, :supply_id, :payment_status, :grn_final_amount, :cash_pay_amount, :chq_pay_amount, :due_amount, :complete_time, :record_time)",
        );
        $payStmt->execute([
            "grn_refno" => $grnRefNo,
            "inv_number" => (string) ($draft["invoice_number"] ?? ""),
            "shop_id" => $shopId,
            "supply_id" => (int) ($draft["supplier_id"] ?? 0),
            "payment_status" => $paidStatus,
            "grn_final_amount" => $finalAmount,
            "cash_pay_amount" => $cashAmount,
            "chq_pay_amount" => $chequeAmount,
            "due_amount" => $dueAmount,
            "complete_time" => $paidStatus === 0 ? $recordTime : null,
            "record_time" => $recordTime,
        ]);

        if ($cashAmount > 0) {
            $logStmt = $this->db->prepare(
                "INSERT INTO shop_grn_pay_log
                 (record_time, grn_code, grn_final_amount, pay_type, cash_pay_amount, paid_user)
                 VALUES
                 (:record_time, :grn_code, :grn_final_amount, 0, :cash_pay_amount, :paid_user)",
            );
            $logStmt->execute([
                "record_time" => $recordTime,
                "grn_code" => $grnRefNo,
                "grn_final_amount" => $finalAmount,
                "cash_pay_amount" => $cashAmount,
                "paid_user" => $userId,
            ]);

            $cashBook = $this->db->prepare(
                "INSERT INTO cash_book
                 (op_date, shop, user, pay_type, remark, open_balance, cash_in, cash_out, close_balance)
                 VALUES
                 (:op_date, :shop, :user, 1, :remark, 0, 0, :cash_out, 0)",
            );
            $cashBook->execute([
                "op_date" => $recordTime,
                "shop" => $shopId,
                "user" => $userId,
                "remark" => "Cash payment for GRN " . $grnRefNo . " at GRN added time.",
                "cash_out" => $cashAmount,
            ]);
        }

        if ($chequeAmount > 0) {
            $logStmt = $this->db->prepare(
                "INSERT INTO shop_grn_pay_log
                 (record_time, grn_code, grn_final_amount, pay_type, chq_pay_amount, paid_user)
                 VALUES
                 (:record_time, :grn_code, :grn_final_amount, 0, :chq_pay_amount, :paid_user)",
            );
            $logStmt->execute([
                "record_time" => $recordTime,
                "grn_code" => $grnRefNo,
                "grn_final_amount" => $finalAmount,
                "chq_pay_amount" => $chequeAmount,
                "paid_user" => $userId,
            ]);

            $cashBook = $this->db->prepare(
                "INSERT INTO cash_book
                 (op_date, shop, user, pay_type, remark, open_balance, cash_in, cash_out, close_balance)
                 VALUES
                 (:op_date, :shop, :user, 3, :remark, 0, 0, :cash_out, 0)",
            );
            $cashBook->execute([
                "op_date" => $recordTime,
                "shop" => $shopId,
                "user" => $userId,
                "remark" => "Cheque payment for GRN " . $grnRefNo . " at GRN added time. Cheque number is " . $chequeNumber,
                "cash_out" => $chequeAmount,
            ]);

            $chequeStmt = $this->db->prepare(
                "INSERT INTO account_cheque
                 (type, cheque_number, remark, cheque_value, cheque_date, reminder, reminder_date, record_date)
                 VALUES
                 (1, :cheque_number, :remark, :cheque_value, :cheque_date, :reminder, :reminder_date, :record_date)",
            );
            $chequeStmt->execute([
                "cheque_number" => $chequeNumber,
                "remark" => "Cheque Payment for GRN ID " . $grnRefNo,
                "cheque_value" => $chequeAmount,
                "cheque_date" => $chequeDate !== "" ? $chequeDate : null,
                "reminder" => $chequeReminder,
                "reminder_date" => $chequeReminderDate !== "" ? $chequeReminderDate : null,
                "record_date" => $recordTime,
            ]);
        }
    }
}
