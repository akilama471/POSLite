<?php

declare(strict_types=1);

class Item extends Model
{
    protected $table = "prod_items";

    public function createContext(): array
    {
        $categoryModel = new ProductCategory();
        $operatorModel = new RechargeOperator();

        return [
            "categories" => $categoryModel->allOrdered(),
            "operators" => $operatorModel->activeOrdered(),
            "existingItems" => $this->allOrdered(),
        ];
    }

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM prod_items ORDER BY item_name ASC",
        );

        return $stmt->fetchAll();
    }

    public function namesByCategoryName(string $categoryName): array
    {
        $categoryModel = new ProductCategory();
        $category = $categoryModel->findByName($categoryName);

        if ($category === null) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT item_name FROM prod_items WHERE item_cat = :item_cat ORDER BY item_name ASC",
        );
        $stmt->execute(["item_cat" => (int) $category["catid"]]);

        return array_map(
            static fn (array $row): string => (string) $row["item_name"],
            $stmt->fetchAll(),
        );
    }

    public function searchDetailed(string $term = ""): array
    {
        $stmt = $this->db->prepare(
            "SELECT item.*,
                    category.catname AS category_name,
                    card.remark AS card_remark,
                    card.operator AS operator_id,
                    operator.operator_name
             FROM prod_items AS item
             LEFT JOIN prod_category AS category
               ON category.catid = item.item_cat
             LEFT JOIN shop_rcv_cards AS card
               ON card.prod_id = item.item_id
             LEFT JOIN shop_rcv_operator AS operator
               ON operator.recordid = card.operator
             WHERE item.item_name LIKE :term
             ORDER BY item.item_name ASC",
        );
        $stmt->execute(["term" => "%" . $term . "%"]);

        return $stmt->fetchAll();
    }

    public function findDetailedById(int $itemId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT item.*,
                    category.catname AS category_name,
                    card.remark AS card_remark,
                    card.operator AS operator_id,
                    operator.operator_name
             FROM prod_items AS item
             LEFT JOIN prod_category AS category
               ON category.catid = item.item_cat
             LEFT JOIN shop_rcv_cards AS card
               ON card.prod_id = item.item_id
             LEFT JOIN shop_rcv_operator AS operator
               ON operator.recordid = card.operator
             WHERE item.item_id = :item_id
             LIMIT 1",
        );
        $stmt->execute(["item_id" => $itemId]);

        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM prod_items WHERE item_name = :item_name LIMIT 1",
        );
        $stmt->execute(["item_name" => $name]);

        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function findById(int $itemId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM prod_items WHERE item_id = :item_id LIMIT 1",
        );
        $stmt->execute(["item_id" => $itemId]);

        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM prod_items WHERE item_name = :name";
        $params = ["name" => $name];

        if ($excludeId !== null) {
            $sql .= " AND item_id <> :item_id";
            $params["item_id"] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createItem(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $itemStmt = $this->db->prepare(
                "INSERT INTO prod_items (item_name, item_cat, used_type, eff_date)
                 VALUES (:item_name, :item_cat, :used_type, :eff_date)",
            );
            $itemStmt->execute([
                "item_name" => $data["item_name"],
                "item_cat" => $data["item_cat"],
                "used_type" => $data["used_type"],
                "eff_date" => $data["eff_date"],
            ]);

            $itemId = (int) $this->db->lastInsertId();

            if ((int) $data["used_type"] === 3) {
                $cardStmt = $this->db->prepare(
                    "INSERT INTO shop_rcv_cards (cat_id, prod_id, card_name, operator, remark, status, eff_date)
                     VALUES (:cat_id, :prod_id, :card_name, :operator, :remark, 1, :eff_date)",
                );
                $cardStmt->execute([
                    "cat_id" => $data["item_cat"],
                    "prod_id" => $itemId,
                    "card_name" => $data["item_name"],
                    "operator" => $data["operator_id"],
                    "remark" => $data["card_remark"],
                    "eff_date" => $data["eff_date"],
                ]);
            }

            $this->db->commit();
            return $itemId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateItem(int $itemId, array $data): bool
    {
        $this->db->beginTransaction();

        try {
            $itemStmt = $this->db->prepare(
                "UPDATE prod_items
                 SET item_name = :item_name
                 WHERE item_id = :item_id",
            );
            $itemStmt->execute([
                "item_id" => $itemId,
                "item_name" => $data["item_name"],
            ]);

            if ((int) $data["used_type"] === 3) {
                $cardStmt = $this->db->prepare(
                    "UPDATE shop_rcv_cards
                     SET card_name = :card_name,
                         remark = :remark
                     WHERE prod_id = :prod_id",
                );
                $cardStmt->execute([
                    "prod_id" => $itemId,
                    "card_name" => $data["item_name"],
                    "remark" => $data["card_remark"],
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function stockSearch(string $name = "", string $code = ""): array
    {
        if ($name !== "") {
            $item = $this->findByName($name);

            if ($item === null) {
                return [];
            }

            return match ((int) $item["used_type"]) {
                1 => [[
                    "type" => "barcode",
                    "title" => "Barcode Controlled Stock",
                    "rows" => $this->barcodeStockByItem((int) $item["item_id"]),
                ]],
                2 => [[
                    "type" => "imei",
                    "title" => "IMEI Controlled Stock",
                    "rows" => $this->imeiStockByItem((int) $item["item_id"]),
                ]],
                3 => [[
                    "type" => "recharge",
                    "title" => "Recharge Card Stock",
                    "rows" => $this->rechargeStockByItem((int) $item["item_id"]),
                ]],
                default => [],
            };
        }

        if ($code !== "") {
            $barcodeRows = $this->barcodeStockByCode($code);
            if ($barcodeRows !== []) {
                return [[
                    "type" => "barcode",
                    "title" => "Barcode Match",
                    "rows" => $barcodeRows,
                ]];
            }

            $imeiRows = $this->imeiStockByCode($code);
            if ($imeiRows !== []) {
                return [[
                    "type" => "imei",
                    "title" => "IMEI Match",
                    "rows" => $imeiRows,
                ]];
            }
        }

        return [];
    }

    public function posLookupByName(string $name, int $shopId): array
    {
        $item = $this->findByName($name);

        if ($item === null) {
            return $this->emptyPosResult("No Data Matched");
        }

        return match ((int) $item["used_type"]) {
            1 => $this->barcodePosByItem($item, $shopId),
            2 => $this->imeiPosByItem($item, $shopId),
            3 => $this->rechargePosByItem($item, $shopId),
            default => $this->emptyPosResult("No Data Matched"),
        };
    }

    public function posLookupByCode(string $code, int $shopId): array
    {
        $barcode = $this->barcodePosByCode($code, $shopId);
        if ($barcode !== null) {
            return $barcode;
        }

        $imei = $this->imeiPosByCode($code, $shopId);
        if ($imei !== null) {
            return $imei;
        }

        return $this->emptyPosResult("No Data Matched", true);
    }

    public function imeiBulkMatches(int $itemId, int $categoryId, int $shopId, array $imeis): array
    {
        $imeis = array_values(array_unique(array_filter(array_map(
            static fn (mixed $imei): string => trim((string) $imei),
            $imeis,
        ))));

        if ($imeis === []) {
            return [];
        }

        [$shopSql, $params] = $this->shopScopeClause($shopId, "stock.stock_in_shop");
        $params["item_id"] = $itemId;
        $params["item_cat"] = $categoryId;

        $placeholders = [];
        foreach ($imeis as $index => $imei) {
            $key = "imei_" . $index;
            $placeholders[] = ":" . $key;
            $params[$key] = $imei;
        }

        $stmt = $this->db->prepare(
            "SELECT *
             FROM shop_stock_imei AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.item_cat_id = :item_cat
               AND stock.stock_current > 0
               AND stock.stock_status = 1
               AND {$shopSql}
               AND stock.imei_no IN (" . implode(", ", $placeholders) . ")",
        );
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(string) $row["imei_no"]] = $row;
        }

        return $rows;
    }

    private function barcodeStockByItem(int $itemId): array
    {
        $stmt = $this->db->prepare(
            "SELECT stock.item_name,
                    stock.item_sell_price,
                    stock.item_cost_price,
                    stock.item_other_price,
                    stock.supplier_id,
                    supplier.supplier_name,
                    stock.stock_current,
                    stock.gen_refno,
                    stock.grn_refno,
                    shop.shop_info_name
             FROM shop_stock_item AS stock
             LEFT JOIN shop_supplier AS supplier
               ON supplier.supplierid = stock.supplier_id
             LEFT JOIN sys_shop AS shop
               ON shop.shopid = stock.stock_in_shop
             WHERE stock.item_name_id = :item_id
               AND stock.stock_status = 1
               AND stock.stock_current > 0",
        );
        $stmt->execute(["item_id" => $itemId]);

        return $stmt->fetchAll();
    }

    private function imeiStockByItem(int $itemId): array
    {
        $stmt = $this->db->prepare(
            "SELECT stock.item_name,
                    stock.item_sell_price,
                    stock.item_cost_price,
                    stock.item_other_price,
                    stock.supplier_id,
                    supplier.supplier_name,
                    stock.imei_no,
                    stock.item_color,
                    stock.grn_refno,
                    shop.shop_info_name
             FROM shop_stock_imei AS stock
             LEFT JOIN shop_supplier AS supplier
               ON supplier.supplierid = stock.supplier_id
             LEFT JOIN sys_shop AS shop
               ON shop.shopid = stock.stock_in_shop
             WHERE stock.item_name_id = :item_id
               AND stock.stock_status = 1
               AND stock.stock_current > 0",
        );
        $stmt->execute(["item_id" => $itemId]);

        return $stmt->fetchAll();
    }

    private function rechargeStockByItem(int $itemId): array
    {
        $stmt = $this->db->prepare(
            "SELECT stock.card_name,
                    stock.sell_price,
                    stock.cost_price,
                    stock.other_price,
                    stock.current_stock,
                    stock.recordid AS grn_refno,
                    shop.shop_info_name
             FROM shop_rcv_stock AS stock
             LEFT JOIN sys_shop AS shop
               ON shop.shopid = stock.stock_in_shop
             WHERE stock.item_name_id = :item_id
               AND stock.stock_status = 1",
        );
        $stmt->execute(["item_id" => $itemId]);

        return $stmt->fetchAll();
    }

    private function barcodeStockByCode(string $code): array
    {
        $stmt = $this->db->prepare(
            "SELECT stock.item_name,
                    stock.item_sell_price,
                    stock.item_cost_price,
                    stock.item_other_price,
                    stock.supplier_id,
                    supplier.supplier_name,
                    stock.stock_current,
                    stock.gen_refno,
                    stock.grn_refno,
                    shop.shop_info_name
             FROM shop_stock_item AS stock
             LEFT JOIN shop_supplier AS supplier
               ON supplier.supplierid = stock.supplier_id
             LEFT JOIN sys_shop AS shop
               ON shop.shopid = stock.stock_in_shop
             WHERE stock.gen_refno = :code
               AND stock.stock_status = 1
               AND stock.stock_current > 0",
        );
        $stmt->execute(["code" => $code]);

        return $stmt->fetchAll();
    }

    private function imeiStockByCode(string $code): array
    {
        $stmt = $this->db->prepare(
            "SELECT stock.item_name,
                    stock.item_sell_price,
                    stock.item_cost_price,
                    stock.item_other_price,
                    stock.supplier_id,
                    supplier.supplier_name,
                    stock.imei_no,
                    stock.item_color,
                    stock.grn_refno,
                    shop.shop_info_name
             FROM shop_stock_imei AS stock
             LEFT JOIN shop_supplier AS supplier
               ON supplier.supplierid = stock.supplier_id
             LEFT JOIN sys_shop AS shop
               ON shop.shopid = stock.stock_in_shop
             WHERE stock.imei_no = :code
               AND stock.stock_status = 1
               AND stock.stock_current > 0",
        );
        $stmt->execute(["code" => $code]);

        return $stmt->fetchAll();
    }

    private function barcodePosByItem(array $item, int $shopId): array
    {
        [$shopSql, $params] = $this->shopScopeClause($shopId, "stock.stock_in_shop");
        $params["item_id"] = (int) $item["item_id"];
        $params["item_cat"] = (int) $item["item_cat"];

        $stmt = $this->db->prepare(
            "SELECT *
             FROM shop_stock_item AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.item_cat_id = :item_cat
               AND stock.stock_current > 0
               AND stock.stock_status < 2
               AND {$shopSql}
             ORDER BY stock.item_stock_id ASC
             LIMIT 1",
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        if ($row === false) {
            return $this->emptyPosResult("No Stock Found");
        }

        $total = $this->sumBarcodeStock((int) $item["item_id"], (int) $item["item_cat"], $shopId, false);

        return $this->buildPosResult([
            "item_id" => (int) $item["item_id"],
            "cat_id" => (int) $item["item_cat"],
            "name" => (string) ($row["item_name"] ?? ""),
            "type" => (string) ($item["used_type"] ?? ""),
            "cost" => (string) ($row["item_cost_price"] ?? ""),
            "sell" => (string) ($row["item_sell_price"] ?? ""),
            "low" => (string) ($row["item_low_price"] ?? ""),
            "other" => (string) ($row["item_other_price"] ?? ""),
            "warranty" => trim(((string) ($row["warranty_span"] ?? "")) . " " . ((string) ($row["warranty_type"] ?? ""))),
            "stock_total" => $total,
            "code" => (string) ($row["gen_refno"] ?? ""),
            "row_id" => (string) ($row["item_stock_id"] ?? ""),
        ]);
    }

    private function imeiPosByItem(array $item, int $shopId): array
    {
        [$shopSql, $params] = $this->shopScopeClause($shopId, "stock.stock_in_shop");
        $params["item_id"] = (int) $item["item_id"];
        $params["item_cat"] = (int) $item["item_cat"];

        $stmt = $this->db->prepare(
            "SELECT *
             FROM shop_stock_imei AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.item_cat_id = :item_cat
               AND stock.stock_current > 0
               AND stock.stock_status < 2
               AND {$shopSql}
             ORDER BY stock.item_stock_id_imei ASC
             LIMIT 1",
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        if ($row === false) {
            return $this->emptyPosResult("No Stock Found");
        }

        $total = $this->sumImeiStock((int) $item["item_id"], (int) $item["item_cat"], $shopId, true);

        return $this->buildPosResult([
            "item_id" => (int) $item["item_id"],
            "cat_id" => (int) $item["item_cat"],
            "name" => (string) ($row["item_name"] ?? ""),
            "type" => (string) ($item["used_type"] ?? ""),
            "cost" => (string) ($row["item_cost_price"] ?? ""),
            "sell" => (string) ($row["item_sell_price"] ?? ""),
            "low" => (string) ($row["item_low_price"] ?? ""),
            "other" => (string) ($row["item_other_price"] ?? ""),
            "warranty" => trim(((string) ($row["warranty_span"] ?? "")) . " " . ((string) ($row["warranty_type"] ?? ""))),
            "stock_total" => $total,
            "code" => (string) ($row["imei_no"] ?? ""),
            "row_id" => (string) ($row["item_stock_id_imei"] ?? ""),
        ]);
    }

    private function rechargePosByItem(array $item, int $shopId): array
    {
        [$shopSql, $params] = $this->shopScopeClause($shopId, "stock.stock_in_shop");
        $params["item_id"] = (int) $item["item_id"];
        $params["item_cat"] = (int) $item["item_cat"];

        $stmt = $this->db->prepare(
            "SELECT *
             FROM shop_rcv_stock AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.item_cat_id = :item_cat
               AND stock.current_stock > 0
               AND {$shopSql}
             ORDER BY stock.recordid ASC
             LIMIT 1",
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        if ($row === false) {
            return $this->emptyPosResult("No Stock Found");
        }

        $total = $this->sumRechargeStock((int) $item["item_id"], (int) $item["item_cat"], $shopId);

        return $this->buildPosResult([
            "item_id" => (int) $item["item_id"],
            "cat_id" => (int) $item["item_cat"],
            "name" => (string) ($row["card_name"] ?? ""),
            "type" => (string) ($item["used_type"] ?? ""),
            "cost" => (string) ($row["cost_price"] ?? ""),
            "sell" => (string) ($row["sell_price"] ?? ""),
            "low" => (string) ($row["low_price"] ?? ""),
            "other" => (string) ($row["other_price"] ?? ""),
            "warranty" => "",
            "stock_total" => $total,
            "code" => "",
            "row_id" => (string) ($row["recordid"] ?? ""),
        ]);
    }

    private function barcodePosByCode(string $code, int $shopId): ?array
    {
        [$shopSql, $params] = $this->shopScopeClause($shopId, "stock.stock_in_shop");
        $params["code"] = $code;

        $stmt = $this->db->prepare(
            "SELECT *
             FROM shop_stock_item AS stock
             WHERE stock.gen_refno = :code
               AND stock.stock_current > 0
               AND stock.stock_status = 1
               AND {$shopSql}
             LIMIT 1",
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $total = $this->sumBarcodeStock((int) $row["item_name_id"], (int) $row["item_cat_id"], $shopId, true, $code);

        return $this->buildPosResult([
            "item_id" => (int) $row["item_name_id"],
            "cat_id" => (int) $row["item_cat_id"],
            "name" => (string) ($row["item_name"] ?? ""),
            "type" => "1",
            "cost" => (string) ($row["item_cost_price"] ?? ""),
            "sell" => (string) ($row["item_sell_price"] ?? ""),
            "low" => (string) ($row["item_low_price"] ?? ""),
            "other" => (string) ($row["item_other_price"] ?? ""),
            "warranty" => trim(((string) ($row["warranty_span"] ?? "")) . " " . ((string) ($row["warranty_type"] ?? ""))),
            "stock_total" => $total,
            "code" => (string) ($row["gen_refno"] ?? ""),
            "row_id" => (string) ($row["item_stock_id"] ?? ""),
            "supplier_id" => (string) ($row["supplier_id"] ?? ""),
        ], true);
    }

    private function imeiPosByCode(string $code, int $shopId): ?array
    {
        [$shopSql, $params] = $this->shopScopeClause($shopId, "stock.stock_in_shop");
        $params["code"] = $code;

        $stmt = $this->db->prepare(
            "SELECT *
             FROM shop_stock_imei AS stock
             WHERE stock.imei_no = :code
               AND stock.stock_current > 0
               AND stock.stock_status = 1
               AND {$shopSql}
             LIMIT 1",
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $total = $this->sumImeiStock((int) $row["item_name_id"], (int) $row["item_cat_id"], $shopId, false);

        return $this->buildPosResult([
            "item_id" => (int) $row["item_name_id"],
            "cat_id" => (int) $row["item_cat_id"],
            "name" => (string) ($row["item_name"] ?? ""),
            "type" => "2",
            "cost" => (string) ($row["item_cost_price"] ?? ""),
            "sell" => (string) ($row["item_sell_price"] ?? ""),
            "low" => (string) ($row["item_low_price"] ?? ""),
            "other" => (string) ($row["item_other_price"] ?? ""),
            "warranty" => trim(((string) ($row["warranty_span"] ?? "")) . " " . ((string) ($row["warranty_type"] ?? ""))),
            "stock_total" => $total,
            "code" => (string) ($row["imei_no"] ?? ""),
            "row_id" => (string) ($row["item_stock_id_imei"] ?? ""),
            "supplier_id" => (string) ($row["supplier_id"] ?? ""),
        ], true);
    }

    private function sumBarcodeStock(int $itemId, int $categoryId, int $shopId, bool $statusEqualsOne, string $code = ""): string
    {
        [$shopSql, $params] = $this->shopScopeClause($shopId, "stock.stock_in_shop");
        $params["item_id"] = $itemId;
        $params["item_cat"] = $categoryId;

        $statusSql = $statusEqualsOne ? "stock.stock_status = 1" : "stock.stock_status < 2";
        $codeSql = "";

        if ($code !== "") {
            $codeSql = " AND stock.gen_refno = :code";
            $params["code"] = $code;
        }

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(stock.stock_current), 0)
             FROM shop_stock_item AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.item_cat_id = :item_cat
               AND {$statusSql}
               AND {$shopSql}{$codeSql}",
        );
        $stmt->execute($params);

        return (string) $stmt->fetchColumn();
    }

    private function sumImeiStock(int $itemId, int $categoryId, int $shopId, bool $onlyPositive): string
    {
        [$shopSql, $params] = $this->shopScopeClause($shopId, "stock.stock_in_shop");
        $params["item_id"] = $itemId;
        $params["item_cat"] = $categoryId;

        $positiveSql = $onlyPositive ? " AND stock.stock_current > 0" : "";

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(stock.stock_current), 0)
             FROM shop_stock_imei AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.item_cat_id = :item_cat
               AND stock.stock_status = 1
               AND {$shopSql}{$positiveSql}",
        );
        $stmt->execute($params);

        return (string) $stmt->fetchColumn();
    }

    private function sumRechargeStock(int $itemId, int $categoryId, int $shopId): string
    {
        [$shopSql, $params] = $this->shopScopeClause($shopId, "stock.stock_in_shop");
        $params["item_id"] = $itemId;
        $params["item_cat"] = $categoryId;

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(stock.current_stock), 0)
             FROM shop_rcv_stock AS stock
             WHERE stock.item_name_id = :item_id
               AND stock.item_cat_id = :item_cat
               AND stock.current_stock > 0
               AND {$shopSql}",
        );
        $stmt->execute($params);

        return (string) $stmt->fetchColumn();
    }

    private function shopScopeClause(int $shopId, string $column): array
    {
        if ($shopId > 0) {
            return ["{$column} = :shop_id", ["shop_id" => $shopId]];
        }

        return ["{$column} > 0", []];
    }

    private function emptyPosResult(string $name, bool $withSupplier = false): array
    {
        $result = $this->buildPosResult([
            "item_id" => "",
            "cat_id" => "",
            "name" => $name,
            "type" => "",
            "cost" => "",
            "sell" => "",
            "low" => "",
            "other" => "",
            "warranty" => "",
            "stock_total" => "",
            "code" => "",
            "row_id" => "",
            "supplier_id" => "",
        ], $withSupplier);

        return $result;
    }

    private function buildPosResult(array $data, bool $withSupplier = false): array
    {
        $result = [
            "itm_selectid" => (string) $data["item_id"],
            "itm_selcatid" => (string) $data["cat_id"],
            "itm_selctnme" => (string) $data["name"],
            "itm_itmstype" => (string) $data["type"],
            "itm_costprce" => (string) $data["cost"],
            "itm_sellpris" => (string) $data["sell"],
            "itm_lowprise" => (string) $data["low"],
            "itm_oterprse" => (string) $data["other"],
            "itm_warntyad" => (string) $data["warranty"],
            "itm_stktotal" => (string) $data["stock_total"],
            "itm_imeicode" => (string) $data["code"],
            "row_ids_data" => (string) $data["row_id"],
        ];

        if ($withSupplier) {
            $result["itm_suply_id"] = (string) ($data["supplier_id"] ?? "");
        }

        return $result;
    }

    public static function typeLabel(int $usedType): string
    {
        return match ($usedType) {
            1 => "By Item Code",
            2 => "By IMEI Number",
            3 => "By Recharge Card",
            default => "Unknown",
        };
    }
}
