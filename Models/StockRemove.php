<?php

declare(strict_types=1);

class StockRemove extends Model
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
                    stock.item_name,
                    stock.gen_refno AS item_code,
                    stock.stock_current AS stock_current,
                    stock.stock_in_shop,
                    stock.stock_add_dt
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
                    stock.item_name,
                    stock.imei_no AS item_code,
                    stock.stock_current AS stock_current,
                    stock.stock_in_shop,
                    stock.stock_add_dt
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
                        stock.item_name,
                        stock.gen_refno AS item_code,
                        stock.stock_current AS stock_current,
                        stock.stock_in_shop,
                        stock.stock_add_dt
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
                        stock.item_name,
                        stock.imei_no AS item_code,
                        stock.stock_current AS stock_current,
                        stock.stock_in_shop,
                        stock.stock_add_dt
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
                        stock.card_name AS item_name,
                        '' AS item_code,
                        stock.current_stock AS stock_current,
                        stock.stock_in_shop,
                        stock.last_upd AS stock_add_dt
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

    public function removeStock(array $auth, int $objectType, int $rowId, string $reason): void
    {
        $shopId = (int) ($auth["shop_id"] ?? 0);
        $userId = (int) ($auth["user_id"] ?? 0);
        $userName = (string) ($auth["user_name"] ?? "user");
        $reason = trim($reason);

        if ($reason === "") {
            throw new RuntimeException("Reason is required for manual stock removal.");
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

                $itemName = $record["item_name"] ?? "";
                $itemCode = $record["gen_refno"] ?? "";
                $currentStock = (int) $record["stock_current"];
                $remark = "{$userName} user deleted stock records. Deleted item is {$itemName} (Barcode:{$itemCode}). At the deleted time, current stock is {$currentStock}.";

                $updStmt = $this->db->prepare("UPDATE shop_stock_item SET stock_status = 4 WHERE item_stock_id = :row_id");
                $updStmt->execute(["row_id" => $rowId]);

            } elseif ($objectType === 2) {
                $stmt = $this->db->prepare("SELECT * FROM shop_stock_imei WHERE item_stock_id_imei = :row_id LIMIT 1 FOR UPDATE");
                $stmt->execute(["row_id" => $rowId]);
                $record = $stmt->fetch();
                
                if (!$record) {
                    throw new RuntimeException("Stock record not found.");
                }

                $itemName = $record["item_name"] ?? "";
                $itemCode = $record["imei_no"] ?? "";
                $currentStock = (int) $record["stock_current"];
                $remark = "{$userName} user deleted stock records. Deleted item is {$itemName} (IMEI No:{$itemCode}). At the deleted time, current stock is {$currentStock}.";

                $updStmt = $this->db->prepare("UPDATE shop_stock_imei SET stock_status = 4 WHERE item_stock_id_imei = :row_id");
                $updStmt->execute(["row_id" => $rowId]);

            } elseif ($objectType === 3) {
                $stmt = $this->db->prepare("SELECT * FROM shop_rcv_stock WHERE recordid = :row_id LIMIT 1 FOR UPDATE");
                $stmt->execute(["row_id" => $rowId]);
                $record = $stmt->fetch();
                
                if (!$record) {
                    throw new RuntimeException("Stock record not found.");
                }

                $itemName = $record["card_name"] ?? "";
                $currentStock = (int) $record["current_stock"];
                $remark = "{$userName} user deleted stock records. Edited item is {$itemName}. At the deleted time, current stock is {$currentStock}.";

                $updStmt = $this->db->prepare("UPDATE shop_rcv_stock SET stock_status = 4 WHERE recordid = :row_id");
                $updStmt->execute(["row_id" => $rowId]);

            } else {
                throw new RuntimeException("Invalid object type for removal.");
            }

            $logStmt = $this->db->prepare(
                "INSERT INTO stock_delete_log (shop_id, operator, type, edit_row, sys_remark, reason, operation_time) 
                 VALUES (:shop_id, :operator, :type, :edit_row, :sys_remark, :reason, :operation_time)"
            );
            $logStmt->execute([
                "shop_id" => $shopId,
                "operator" => $userId,
                "type" => $objectType,
                "edit_row" => $rowId,
                "sys_remark" => $remark,
                "reason" => $reason,
                "operation_time" => date('Y-m-d H:i:s'),
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
            "stock_add_dt" => (string) ($row["stock_add_dt"] ?? ""),
        ];
    }
}
