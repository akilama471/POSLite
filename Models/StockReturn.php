<?php

declare(strict_types=1);

class StockReturn extends Model
{
    public function findCandidatesByCode(string $code, int $shopId): array
    {
        $code = trim($code);
        if ($code === "") {
            return [];
        }

        $rows = [];

        // Type 1: Normal Items
        $barcodeStmt = $this->db->prepare(
            "SELECT 1 AS object_type,
                    stock.item_stock_id AS row_id,
                    stock.item_name_id,
                    stock.item_name,
                    stock.gen_refno AS item_code,
                    stock.stock_current AS stock_current,
                    stock.item_cost_price AS part_cost,
                    stock.stock_in_shop
             FROM shop_stock_item AS stock
             WHERE stock.gen_refno = :code
               AND stock.stock_status = 1
               AND stock.stock_current > 0
               AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
             ORDER BY stock.item_stock_id DESC"
        );
        $barcodeStmt->execute([
            "code" => $code,
            "shop_id" => $shopId,
        ]);
        foreach ($barcodeStmt->fetchAll() as $row) {
            $rows[] = $this->normalizeCandidateRow($row);
        }

        // Type 2: IMEI Items
        $imeiStmt = $this->db->prepare(
            "SELECT 2 AS object_type,
                    stock.item_stock_id_imei AS row_id,
                    stock.item_name_id,
                    stock.item_name,
                    stock.imei_no AS item_code,
                    stock.stock_current AS stock_current,
                    stock.item_cost_price AS part_cost,
                    stock.stock_in_shop
             FROM shop_stock_imei AS stock
             WHERE stock.imei_no = :code
               AND stock.stock_status = 1
               AND stock.stock_current > 0
               AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
             ORDER BY stock.item_stock_id_imei DESC"
        );
        $imeiStmt->execute([
            "code" => $code,
            "shop_id" => $shopId,
        ]);
        foreach ($imeiStmt->fetchAll() as $row) {
            $rows[] = $this->normalizeCandidateRow($row);
        }

        return $rows;
    }

    public function findCandidatesByItemName(string $itemName, int $shopId): array
    {
        $itemName = trim($itemName);
        if ($itemName === "") {
            return [];
        }

        $itemModel = new Item();
        $item = $itemModel->findByName($itemName);
        if ($item === null) {
            return [];
        }

        $itemId = (int) ($item["item_id"] ?? 0);
        $usedType = (int) ($item["used_type"] ?? 0);
        $rows = [];

        if ($usedType === 1) {
            $barcodeStmt = $this->db->prepare(
                "SELECT 1 AS object_type,
                        stock.item_stock_id AS row_id,
                        stock.item_name_id,
                        stock.item_name,
                        stock.gen_refno AS item_code,
                        stock.stock_current AS stock_current,
                        stock.item_cost_price AS part_cost,
                        stock.stock_in_shop
                 FROM shop_stock_item AS stock
                 WHERE stock.item_name_id = :item_id
                   AND stock.stock_status = 1
                   AND stock.stock_current > 0
                   AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
                 ORDER BY stock.item_stock_id DESC"
            );
            $barcodeStmt->execute([
                "item_id" => $itemId,
                "shop_id" => $shopId,
            ]);
            foreach ($barcodeStmt->fetchAll() as $row) {
                $rows[] = $this->normalizeCandidateRow($row);
            }
        } elseif ($usedType === 2) {
            $imeiStmt = $this->db->prepare(
                "SELECT 2 AS object_type,
                        stock.item_stock_id_imei AS row_id,
                        stock.item_name_id,
                        stock.item_name,
                        stock.imei_no AS item_code,
                        stock.stock_current AS stock_current,
                        stock.item_cost_price AS part_cost,
                        stock.stock_in_shop
                 FROM shop_stock_imei AS stock
                 WHERE stock.item_name_id = :item_id
                   AND stock.stock_status = 1
                   AND stock.stock_current > 0
                   AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
                 ORDER BY stock.item_stock_id_imei DESC"
            );
            $imeiStmt->execute([
                "item_id" => $itemId,
                "shop_id" => $shopId,
            ]);
            foreach ($imeiStmt->fetchAll() as $row) {
                $rows[] = $this->normalizeCandidateRow($row);
            }
        } elseif ($usedType === 3) {
            $rcvStmt = $this->db->prepare(
                "SELECT 3 AS object_type,
                        stock.recordid AS row_id,
                        stock.item_name_id,
                        stock.card_name AS item_name,
                        '' AS item_code,
                        stock.current_stock AS stock_current,
                        stock.cost_price AS part_cost,
                        stock.stock_in_shop
                 FROM shop_rcv_stock AS stock
                 WHERE stock.item_name_id = :item_id
                   AND stock.stock_status = 1
                   AND stock.current_stock > 0
                   AND (:shop_id = 0 OR stock.stock_in_shop = :shop_id)
                 ORDER BY stock.recordid DESC"
            );
            $rcvStmt->execute([
                "item_id" => $itemId,
                "shop_id" => $shopId,
            ]);
            foreach ($rcvStmt->fetchAll() as $row) {
                $rows[] = $this->normalizeCandidateRow($row);
            }
        }

        return $rows;
    }

    public function returnStock(array $auth, int $objectType, int $rowId, int $qty, string $reason): void
    {
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $userId = (int) ($auth["user_id"] ?? 0);
        $reason = trim($reason);

        if ($qty <= 0) {
            throw new RuntimeException("Quantity must be greater than zero.");
        }
        if ($reason === "") {
            throw new RuntimeException("Return reason is required.");
        }
        if ($objectType === 2 && $qty !== 1) {
            throw new RuntimeException("IMEI items must be returned one by one.");
        }

        $this->db->beginTransaction();

        try {
            if ($objectType === 1) {
                $stmt = $this->db->prepare("SELECT * FROM shop_stock_item WHERE item_stock_id = :row_id LIMIT 1 FOR UPDATE");
                $stmt->execute(["row_id" => $rowId]);
                $record = $stmt->fetch();
                
                if (!$record) {
                    throw new RuntimeException("Stock record not found.");
                }

                $currentStock = (int) $record["stock_current"];
                if ($qty > $currentStock) {
                    throw new RuntimeException("Return quantity cannot exceed current stock.");
                }

                $newStock = $currentStock - $qty;
                $updStmt = $this->db->prepare("UPDATE shop_stock_item SET stock_current = :new_stock WHERE item_stock_id = :row_id");
                $updStmt->execute(["new_stock" => $newStock, "row_id" => $rowId]);

            } elseif ($objectType === 2) {
                $stmt = $this->db->prepare("SELECT * FROM shop_stock_imei WHERE item_stock_id_imei = :row_id LIMIT 1 FOR UPDATE");
                $stmt->execute(["row_id" => $rowId]);
                $record = $stmt->fetch();
                
                if (!$record) {
                    throw new RuntimeException("Stock record not found.");
                }

                $currentStock = (int) $record["stock_current"];
                if ($qty > $currentStock) {
                    throw new RuntimeException("Return quantity cannot exceed current stock.");
                }

                $newStock = $currentStock - $qty;
                $updStmt = $this->db->prepare("UPDATE shop_stock_imei SET stock_current = :new_stock WHERE item_stock_id_imei = :row_id");
                $updStmt->execute(["new_stock" => $newStock, "row_id" => $rowId]);

            } elseif ($objectType === 3) {
                $stmt = $this->db->prepare("SELECT * FROM shop_rcv_stock WHERE recordid = :row_id LIMIT 1 FOR UPDATE");
                $stmt->execute(["row_id" => $rowId]);
                $record = $stmt->fetch();
                
                if (!$record) {
                    throw new RuntimeException("Stock record not found.");
                }

                $currentStock = (int) $record["current_stock"];
                if ($qty > $currentStock) {
                    throw new RuntimeException("Return quantity cannot exceed current stock.");
                }

                $newStock = $currentStock - $qty;
                $updStmt = $this->db->prepare("UPDATE shop_rcv_stock SET current_stock = :new_stock WHERE recordid = :row_id");
                $updStmt->execute(["new_stock" => $newStock, "row_id" => $rowId]);

            } else {
                throw new RuntimeException("Invalid object type for return.");
            }

            // Get Category ID from item
            $itemNameId = (int) ($record["item_name_id"] ?? 0);
            $catId = 0;
            if ($itemNameId > 0) {
                $itemStmt = $this->db->prepare("SELECT item_cat FROM prod_items WHERE item_id = :id");
                $itemStmt->execute(["id" => $itemNameId]);
                $itemRec = $itemStmt->fetch();
                if ($itemRec) {
                    $catId = (int) $itemRec["item_cat"];
                }
            }

            $partId = $itemNameId;
            $itemName = $record["item_name"] ?? ($record["card_name"] ?? "");
            $itemCode = $record["gen_refno"] ?? ($record["imei_no"] ?? "");
            $cost = (float) ($record["item_cost_price"] ?? ($record["cost_price"] ?? 0.0));
            $returnCost = $cost * $qty;
            $stockShop = (int) ($record["stock_in_shop"] ?? 0);

            // Log the return (return_type 3 is direct return)
            $logStmt = $this->db->prepare(
                "INSERT INTO stock_return_log 
                 (return_type, part_id, cat_id, type, item_name, imei_part_no, cost, qty, return_cost, stock_shop, operate_shop, operator, return_reason, activity, create_time) 
                 VALUES 
                 (3, :part_id, :cat_id, :type, :item_name, :imei_part_no, :cost, :qty, :return_cost, :stock_shop, :operate_shop, :operator, :return_reason, 1, :create_time)"
            );
            $logStmt->execute([
                "part_id" => $partId,
                "cat_id" => $catId,
                "type" => $objectType,
                "item_name" => $itemName,
                "imei_part_no" => $itemCode,
                "cost" => $cost,
                "qty" => $qty,
                "return_cost" => $returnCost,
                "stock_shop" => $stockShop,
                "operate_shop" => $shopId,
                "operator" => $userId,
                "return_reason" => $reason,
                "create_time" => date('Y-m-d H:i:s'),
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function normalizeCandidateRow(array $row): array
    {
        $shopId = (int) ($row["stock_in_shop"] ?? 0);
        $shopName = "Unknown Shop";
        if ($shopId > 0) {
            $shopModel = new Shop();
            $shop = $shopModel->findByShopId($shopId);
            if ($shop !== null) {
                $shopName = (string) ($shop["shop_info_name"] ?? $shop["shopname"] ?? "Unknown Shop");
            }
        }

        return [
            "object_type" => (int) ($row["object_type"] ?? 0),
            "row_id" => (int) ($row["row_id"] ?? 0),
            "item_name" => (string) ($row["item_name"] ?? ""),
            "item_code" => (string) ($row["item_code"] ?? ""),
            "stock_current" => (int) ($row["stock_current"] ?? 0),
            "shop_name" => $shopName,
        ];
    }
}
