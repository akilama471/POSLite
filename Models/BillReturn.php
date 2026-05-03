<?php

declare(strict_types=1);

class BillReturn extends Model
{
    protected $table = "alter_bill_billdata";

    public function billForReturn(string $billNumber, int $shopId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM shop_pos_billdetails
             WHERE billnumber = :billnumber
               AND complete = 1
             LIMIT 1",
        );
        $stmt->execute([
            "billnumber" => $billNumber,
        ]);
        $bill = $stmt->fetch();

        if ($bill === false) {
            return null;
        }

        if ($shopId > 0 && (int) ($bill["billed_shop"] ?? 0) !== $shopId) {
            return null;
        }

        $lineStmt = $this->db->prepare(
            "SELECT *
             FROM shop_pos_mainsale
             WHERE billnumber = :billnumber
             ORDER BY recordid ASC",
        );
        $lineStmt->execute([
            "billnumber" => $billNumber,
        ]);

        return [
            "bill" => $bill,
            "lines" => $lineStmt->fetchAll(),
        ];
    }

    public function hasPendingActivity(string $billNumber): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM alter_bill_billdata
             WHERE billnumber = :billnumber
               AND activity_update = 0",
        );
        $stmt->execute([
            "billnumber" => $billNumber,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function historyForBill(string $billNumber, int $shopId): array
    {
        $billCheck = $this->db->prepare(
            "SELECT billnumber, billed_shop, alter_bill
             FROM shop_pos_billdetails
             WHERE billnumber = :billnumber
             LIMIT 1",
        );
        $billCheck->execute([
            "billnumber" => $billNumber,
        ]);
        $bill = $billCheck->fetch();

        if ($bill === false) {
            return [];
        }

        if ($shopId > 0 && (int) ($bill["billed_shop"] ?? 0) !== $shopId) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT *
             FROM alter_bill_billdata
             WHERE billnumber = :billnumber
             ORDER BY alter_times ASC, recordid ASC",
        );
        $stmt->execute([
            "billnumber" => $billNumber,
        ]);
        $events = $stmt->fetchAll();

        return $this->attachReturnItems($events, $billNumber);
    }

    public function pendingActivities(int $shopId): array
    {
        $sql = "SELECT *
                FROM alter_bill_billdata
                WHERE activity_update = 0";
        $params = [];

        if ($shopId > 0) {
            $sql .= " AND alter_shop = :shop_id";
            $params["shop_id"] = $shopId;
        }

        $sql .= " ORDER BY recordid DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        foreach ($events as &$event) {
            $summaryStmt = $this->db->prepare(
                "SELECT COUNT(*) AS item_count, COALESCE(SUM(return_sale), 0) AS total_return_value
                 FROM alter_bill_mainsale
                 WHERE billnumber = :billnumber
                   AND alter_time = :alter_time
                   AND activity = 0",
            );
            $summaryStmt->execute([
                "billnumber" => (string) ($event["billnumber"] ?? ""),
                "alter_time" => (int) ($event["alter_times"] ?? 0),
            ]);
            $summary = $summaryStmt->fetch() ?: [
                "item_count" => 0,
                "total_return_value" => 0,
            ];
            $event["item_count"] = (int) ($summary["item_count"] ?? 0);
            $event["total_return_value"] = (float) ($summary["total_return_value"] ?? 0);
        }
        unset($event);

        return $events;
    }

    public function pendingEvent(string $billNumber, int $alterTime, int $shopId): ?array
    {
        $headerStmt = $this->db->prepare(
            "SELECT *
             FROM alter_bill_billdata
             WHERE billnumber = :billnumber
               AND alter_times = :alter_time
               AND activity_update = 0
             LIMIT 1",
        );
        $headerStmt->execute([
            "billnumber" => $billNumber,
            "alter_time" => $alterTime,
        ]);
        $event = $headerStmt->fetch();

        if ($event === false) {
            return null;
        }

        if ($shopId > 0 && (int) ($event["alter_shop"] ?? 0) !== $shopId) {
            return null;
        }

        $billContext = $this->billForReturn($billNumber, $shopId);
        if ($billContext === null) {
            return null;
        }

        $itemStmt = $this->db->prepare(
            "SELECT *
             FROM alter_bill_mainsale
             WHERE billnumber = :billnumber
               AND alter_time = :alter_time
             ORDER BY recordid ASC",
        );
        $itemStmt->execute([
            "billnumber" => $billNumber,
            "alter_time" => $alterTime,
        ]);

        return [
            "event" => $event,
            "bill" => $billContext["bill"],
            "sale_lines" => $billContext["lines"],
            "items" => $this->attachReturnItems($itemStmt->fetchAll(), $billNumber),
        ];
    }

    public function createReturnRequest(string $billNumber, int $shopId, int $userId, string $reason, array $entries): void
    {
        $reason = trim($reason);

        if ($reason === "") {
            throw new RuntimeException("Reason for return is required.");
        }

        if ($entries === []) {
            throw new RuntimeException("Select at least one item with a return quantity.");
        }

        $context = $this->billForReturn($billNumber, $shopId);
        if ($context === null) {
            throw new RuntimeException("Bill was not found for return processing.");
        }

        if ($this->hasPendingActivity($billNumber)) {
            throw new RuntimeException("This bill already has a pending return activity queue.");
        }

        $bill = $context["bill"];
        $lines = $context["lines"];
        $billedShop = (int) ($bill["billed_shop"] ?? 0);
        $recordTime = date("Y-m-d H:i:s");
        $lineMap = [];
        $normalizedEntries = [];

        foreach ($lines as $line) {
            $lineMap[(int) ($line["recordid"] ?? 0)] = $line;
        }

        foreach ($entries as $entry) {
            $rowId = (int) ($entry["row_id"] ?? 0);
            $returnQty = (int) ($entry["return_qty"] ?? 0);
            $returnType = (int) ($entry["return_type"] ?? 1);

            if ($returnQty < 1) {
                continue;
            }

            if ($returnType !== 1 && $returnType !== 2) {
                throw new RuntimeException("Invalid return option selected.");
            }

            $line = $lineMap[$rowId] ?? null;
            if ($line === null) {
                throw new RuntimeException("A selected bill item was not found.");
            }

            $soldQty = (int) ($line["qty"] ?? 0);
            $lineType = (int) ($line["type"] ?? 0);

            if ($lineType === 2 && $returnQty !== 1) {
                throw new RuntimeException("IMEI return quantity must be exactly 1.");
            }

            if ($returnQty > $soldQty) {
                throw new RuntimeException("Return quantity cannot exceed sold quantity for " . (string) ($line["item_name"] ?? "item") . ".");
            }

            $normalizedEntries[] = [
                "line" => $line,
                "return_qty" => $returnQty,
                "return_type" => $returnType,
            ];
        }

        if ($normalizedEntries === []) {
            throw new RuntimeException("Select at least one item with a return quantity.");
        }

        $this->db->beginTransaction();

        try {
            $alterStmt = $this->db->prepare(
                "SELECT COALESCE(MAX(alter_times), 0)
                 FROM alter_bill_billdata
                 WHERE billnumber = :billnumber
                 FOR UPDATE",
            );
            $alterStmt->execute([
                "billnumber" => $billNumber,
            ]);
            $alterTime = ((int) $alterStmt->fetchColumn()) + 1;

            $insertReturn = $this->db->prepare(
                "INSERT INTO alter_bill_mainsale
                 (alter_time, billnumber, part_id, cat_id, type, item_name, imei_part_no, cost, regular_price, qty, sale_price, discount, sub_total, waranty, return_count, return_cost, return_sale, activity, operation_time)
                 VALUES
                 (:alter_time, :billnumber, :part_id, :cat_id, :type, :item_name, :imei_part_no, :cost, :regular_price, :qty, :sale_price, :discount, :sub_total, :waranty, :return_count, :return_cost, :return_sale, 0, :operation_time)",
            );
            $insertMain = $this->db->prepare(
                "INSERT INTO alter_bill_billdata
                 (billnumber, alter_user, alter_times, alter_reason, bill_shop, alter_shop, activity_update, record_time)
                 VALUES
                 (:billnumber, :alter_user, :alter_times, :alter_reason, :bill_shop, :alter_shop, 0, :record_time)",
            );

            foreach ($normalizedEntries as $entry) {
                $line = $entry["line"];
                $returnQty = (int) $entry["return_qty"];
                $returnType = (int) $entry["return_type"];
                $lineType = (int) ($line["type"] ?? 0);
                $cost = (float) ($line["cost"] ?? 0);
                $salePrice = (float) ($line["sale_price"] ?? 0);
                $soldQty = (int) ($line["qty"] ?? 0);

                $insertReturn->execute([
                    "alter_time" => $alterTime,
                    "billnumber" => $billNumber,
                    "part_id" => (int) ($line["part_id"] ?? 0),
                    "cat_id" => (int) ($line["cat_id"] ?? 0),
                    "type" => $lineType,
                    "item_name" => (string) ($line["item_name"] ?? ""),
                    "imei_part_no" => (string) ($line["imei_part_no"] ?? ""),
                    "cost" => $cost,
                    "regular_price" => (float) ($line["regular_price"] ?? 0),
                    "qty" => $soldQty,
                    "sale_price" => $salePrice,
                    "discount" => (float) ($line["discount"] ?? 0),
                    "sub_total" => (float) ($line["sub_total"] ?? 0),
                    "waranty" => (string) ($line["waranty"] ?? ""),
                    "return_count" => $returnQty,
                    "return_cost" => $returnQty * $cost,
                    "return_sale" => $returnQty * $salePrice,
                    "operation_time" => $recordTime,
                ]);

                if ($returnType === 1) {
                    $this->restockReturnedItem($line, $shopId, $billedShop, $returnQty, $recordTime);
                    $this->insertReturnLog([
                        "return_type" => 1,
                        "billnumber" => $billNumber,
                        "part_id" => (int) ($line["part_id"] ?? 0),
                        "cat_id" => (int) ($line["cat_id"] ?? 0),
                        "type" => $lineType,
                        "item_name" => (string) ($line["item_name"] ?? ""),
                        "imei_part_no" => (string) ($line["imei_part_no"] ?? ""),
                        "cost" => $cost,
                        "qty" => $returnQty,
                        "return_cost" => $returnQty * $cost,
                        "stock_shop" => $billedShop,
                        "operate_shop" => $shopId,
                        "operator" => $userId,
                        "activity" => 2,
                        "finalized_user" => $userId,
                        "finalized_time" => $recordTime,
                        "create_time" => $recordTime,
                    ]);
                    continue;
                }

                $logCount = $lineType === 2 ? 1 : $returnQty;
                for ($i = 0; $i < $logCount; $i++) {
                    $this->insertReturnLog([
                        "return_type" => 2,
                        "billnumber" => $billNumber,
                        "part_id" => (int) ($line["part_id"] ?? 0),
                        "cat_id" => (int) ($line["cat_id"] ?? 0),
                        "type" => $lineType,
                        "item_name" => (string) ($line["item_name"] ?? ""),
                        "imei_part_no" => (string) ($line["imei_part_no"] ?? ""),
                        "cost" => $cost,
                        "qty" => 1,
                        "return_cost" => $cost,
                        "stock_shop" => $billedShop,
                        "operate_shop" => $shopId,
                        "operator" => $userId,
                        "activity" => 1,
                        "create_time" => $recordTime,
                    ]);
                }
            }

            $insertMain->execute([
                "billnumber" => $billNumber,
                "alter_user" => $userId,
                "alter_times" => $alterTime,
                "alter_reason" => $reason,
                "bill_shop" => $billedShop,
                "alter_shop" => $shopId,
                "record_time" => $recordTime,
            ]);

            $updateBill = $this->db->prepare(
                "UPDATE shop_pos_billdetails
                 SET alter_bill = 1
                 WHERE billnumber = :billnumber",
            );
            $updateBill->execute([
                "billnumber" => $billNumber,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function processReplacementSettlement(int $itemRecordId, int $shopId, int $userId, array $payload): void
    {
        $this->db->beginTransaction();

        try {
            $item = $this->findPendingItemForUpdate($itemRecordId, $shopId);
            if ($item === null) {
                throw new RuntimeException("Pending return item was not found.");
            }

            $replaceType = (int) ($payload["replacement_type"] ?? 0);
            $replaceRowId = (int) ($payload["replacement_row_id"] ?? 0);
            $replaceQty = (int) ($payload["replacement_qty"] ?? 0);
            $replacePrice = (float) ($payload["replacement_price"] ?? 0);
            $moneyReturn = (float) ($payload["money_return"] ?? 0);
            $moneyCollect = (float) ($payload["money_collect"] ?? 0);
            $recordTime = date("Y-m-d H:i:s");

            if ($replaceQty < 0 || $replacePrice < 0 || $moneyReturn < 0 || $moneyCollect < 0) {
                throw new RuntimeException("Settlement values cannot be negative.");
            }

            $replaceValue = 0.0;
            $replacement = null;

            if ($replaceType > 0 || $replaceRowId > 0 || $replaceQty > 0 || $replacePrice > 0) {
                if ($replaceType < 1 || $replaceType > 3 || $replaceRowId < 1 || $replaceQty < 1 || $replacePrice <= 0) {
                    throw new RuntimeException("Replacement item details are incomplete.");
                }

                $replacement = $this->loadReplacementStockForUpdate($replaceType, $replaceRowId, $shopId);
                if ($replacement === null) {
                    throw new RuntimeException("Replacement stock item was not found.");
                }

                if ($replaceType === 2 && $replaceQty !== 1) {
                    throw new RuntimeException("IMEI replacement quantity must be exactly 1.");
                }

                $available = $this->replacementAvailableStock($replaceType, $replacement);
                if ($replaceQty > $available) {
                    throw new RuntimeException("Replacement quantity exceeds current stock.");
                }

                $replaceValue = $replaceQty * $replacePrice;
                $this->decrementReplacementStock($replaceType, $replaceRowId, $replaceQty);
            }

            $returnSale = (float) ($item["return_sale"] ?? 0);
            $balance = round($returnSale - $replaceValue - $moneyReturn + $moneyCollect, 2);
            if (abs($balance) > 0.009) {
                throw new RuntimeException("Return value is not fully settled.");
            }

            $infoId = $this->insertAlterInformation([
                "billnumber" => (string) ($item["billnumber"] ?? ""),
                "part_id" => (int) ($replacement["part_id"] ?? 0),
                "cat_id" => (int) ($replacement["cat_id"] ?? 0),
                "type" => (int) ($replacement["type"] ?? 0),
                "item_name" => (string) ($replacement["item_name"] ?? ""),
                "imei_part_no" => (string) ($replacement["code"] ?? "0"),
                "cost" => (float) ($replacement["cost"] ?? 0),
                "regular_price" => $replacePrice,
                "qty" => $replaceQty,
                "sub_total" => $replaceValue,
                "return_money" => $moneyReturn,
                "collect_money" => $moneyCollect,
                "operator" => $userId,
                "operation_time" => $recordTime,
            ]);

            $this->markReturnItemProcessed($itemRecordId, 1, $infoId);

            if ($moneyReturn > 0) {
                $this->insertCashBook([
                    "op_date" => $recordTime,
                    "shop" => $shopId,
                    "user" => $userId,
                    "pay_type" => 1,
                    "remark" => "Return money for bill return. (Bill ID : " . (string) ($item["billnumber"] ?? "") . " and Part name : " . (string) ($item["item_name"] ?? "") . ")",
                    "cash_in" => 0,
                    "cash_out" => $moneyReturn,
                ]);
            }

            if ($moneyCollect > 0) {
                $this->insertCashBook([
                    "op_date" => $recordTime,
                    "shop" => $shopId,
                    "user" => $userId,
                    "pay_type" => 1,
                    "remark" => "Collect money from customer for bill return. (Bill ID : " . (string) ($item["billnumber"] ?? "") . " and Part name : " . (string) ($item["item_name"] ?? "") . ")",
                    "cash_in" => $moneyCollect,
                    "cash_out" => 0,
                ]);
            }

            $this->finalizeEventIfComplete((string) ($item["billnumber"] ?? ""), (int) ($item["alter_time"] ?? 0));
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function processCustomerCredit(int $itemRecordId, int $shopId, int $userId): void
    {
        $this->db->beginTransaction();

        try {
            $item = $this->findPendingItemForUpdate($itemRecordId, $shopId);
            if ($item === null) {
                throw new RuntimeException("Pending return item was not found.");
            }

            $billContext = $this->billForReturn((string) ($item["billnumber"] ?? ""), $shopId);
            if ($billContext === null) {
                throw new RuntimeException("Bill for the pending return item was not found.");
            }

            $customerId = (int) ($billContext["bill"]["customer_id"] ?? 0);
            if ($customerId < 1) {
                throw new RuntimeException("Cash credit return is only allowed for registered customers.");
            }

            $recordTime = date("Y-m-d H:i:s");
            $remark = "Cash Credit Return from Bill ID " . (string) ($item["billnumber"] ?? "") . " For item " . (string) ($item["item_name"] ?? "") . " (" . (int) ($item["return_count"] ?? 0) . " item(s) returned)";

            $stmt = $this->db->prepare(
                "INSERT INTO account_cashcredit_customer
                 (customer, remark, add_operator, amount, status, billnumber, recordtime)
                 VALUES
                 (:customer, :remark, :operator, :amount, 1, :billnumber, :recordtime)",
            );
            $stmt->execute([
                "customer" => $customerId,
                "remark" => $remark,
                "operator" => $userId,
                "amount" => (float) ($item["return_sale"] ?? 0),
                "billnumber" => (string) ($item["billnumber"] ?? ""),
                "recordtime" => $recordTime,
            ]);

            $this->markReturnItemProcessed($itemRecordId, 2, null);
            $this->finalizeEventIfComplete((string) ($item["billnumber"] ?? ""), (int) ($item["alter_time"] ?? 0));
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    private function attachReturnItems(array $events, string $billNumber): array
    {
        if ($events === []) {
            return [];
        }

        $first = $events[0] ?? null;
        if (is_array($first) && array_key_exists("alter_time", $first) && !array_key_exists("alter_times", $first)) {
            $infoStmt = $this->db->prepare(
                "SELECT *
                 FROM alter_bill_information
                 WHERE recordid = :recordid
                 LIMIT 1",
            );

            foreach ($events as &$item) {
                $item["activity_info"] = null;

                if ((int) ($item["activity"] ?? 0) > 0 && (int) ($item["find_record_id"] ?? 0) > 0) {
                    $infoStmt->execute([
                        "recordid" => (int) $item["find_record_id"],
                    ]);
                    $item["activity_info"] = $infoStmt->fetch() ?: null;
                }
            }
            unset($item);

            return $events;
        }

        $itemStmt = $this->db->prepare(
            "SELECT *
             FROM alter_bill_mainsale
             WHERE billnumber = :billnumber
               AND alter_time = :alter_time
             ORDER BY recordid ASC",
        );
        $infoStmt = $this->db->prepare(
            "SELECT *
             FROM alter_bill_information
             WHERE recordid = :recordid
             LIMIT 1",
        );

        foreach ($events as &$event) {
            $itemStmt->execute([
                "billnumber" => $billNumber,
                "alter_time" => (int) ($event["alter_times"] ?? 0),
            ]);
            $items = $itemStmt->fetchAll();

            foreach ($items as &$item) {
                $item["activity_info"] = null;

                if ((int) ($item["activity"] ?? 0) > 0 && (int) ($item["find_record_id"] ?? 0) > 0) {
                    $infoStmt->execute([
                        "recordid" => (int) $item["find_record_id"],
                    ]);
                    $item["activity_info"] = $infoStmt->fetch() ?: null;
                }
            }
            unset($item);

            $event["items"] = $items;
        }
        unset($event);

        return $events;
    }

    private function findPendingItemForUpdate(int $itemRecordId, int $shopId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT item.*, event.alter_shop
             FROM alter_bill_mainsale AS item
             INNER JOIN alter_bill_billdata AS event
               ON event.billnumber = item.billnumber
              AND event.alter_times = item.alter_time
             WHERE item.recordid = :recordid
               AND item.activity = 0
               AND event.activity_update = 0
             LIMIT 1
             FOR UPDATE",
        );
        $stmt->execute([
            "recordid" => $itemRecordId,
        ]);
        $item = $stmt->fetch();

        if ($item === false) {
            return null;
        }

        if ($shopId > 0 && (int) ($item["alter_shop"] ?? 0) !== $shopId) {
            return null;
        }

        return $item;
    }

    private function loadReplacementStockForUpdate(int $type, int $rowId, int $shopId): ?array
    {
        if ($type === 1) {
            $stmt = $this->db->prepare(
                "SELECT item_stock_id AS row_id,
                        item_name_id AS part_id,
                        item_cat_id AS cat_id,
                        item_name,
                        gen_refno AS code,
                        item_cost_price AS cost,
                        stock_current AS stock_current,
                        1 AS type
                 FROM shop_stock_item
                 WHERE item_stock_id = :row_id
                   AND stock_in_shop = :shop_id
                 LIMIT 1
                 FOR UPDATE",
            );
        } elseif ($type === 2) {
            $stmt = $this->db->prepare(
                "SELECT item_stock_id_imei AS row_id,
                        item_name_id AS part_id,
                        item_cat_id AS cat_id,
                        item_name,
                        imei_no AS code,
                        item_cost_price AS cost,
                        stock_current AS stock_current,
                        2 AS type
                 FROM shop_stock_imei
                 WHERE item_stock_id_imei = :row_id
                   AND stock_in_shop = :shop_id
                 LIMIT 1
                 FOR UPDATE",
            );
        } else {
            $stmt = $this->db->prepare(
                "SELECT recordid AS row_id,
                        item_name_id AS part_id,
                        item_cat_id AS cat_id,
                        card_name AS item_name,
                        '0' AS code,
                        cost_price AS cost,
                        current_stock AS stock_current,
                        3 AS type
                 FROM shop_rcv_stock
                 WHERE recordid = :row_id
                   AND stock_in_shop = :shop_id
                 LIMIT 1
                 FOR UPDATE",
            );
        }

        $stmt->execute([
            "row_id" => $rowId,
            "shop_id" => $shopId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function replacementAvailableStock(int $type, array $replacement): int
    {
        return $type === 3
            ? (int) ($replacement["stock_current"] ?? 0)
            : (int) ($replacement["stock_current"] ?? 0);
    }

    private function decrementReplacementStock(int $type, int $rowId, int $qty): void
    {
        if ($type === 1) {
            $stmt = $this->db->prepare(
                "UPDATE shop_stock_item
                 SET stock_current = stock_current - :qty
                 WHERE item_stock_id = :row_id",
            );
        } elseif ($type === 2) {
            $stmt = $this->db->prepare(
                "UPDATE shop_stock_imei
                 SET stock_current = 0
                 WHERE item_stock_id_imei = :row_id",
            );
        } else {
            $stmt = $this->db->prepare(
                "UPDATE shop_rcv_stock
                 SET current_stock = current_stock - :qty
                 WHERE recordid = :row_id",
            );
        }

        $params = ["row_id" => $rowId];
        if ($type !== 2) {
            $params["qty"] = $qty;
        }

        $stmt->execute($params);
    }

    private function insertAlterInformation(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO alter_bill_information
             (billnumber, part_id, cat_id, type, item_name, imei_part_no, cost, regular_price, qty, sub_total, return_money, collect_money, operator, operation_time)
             VALUES
             (:billnumber, :part_id, :cat_id, :type, :item_name, :imei_part_no, :cost, :regular_price, :qty, :sub_total, :return_money, :collect_money, :operator, :operation_time)",
        );
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    private function markReturnItemProcessed(int $itemRecordId, int $activity, ?int $infoId): void
    {
        $sql = "UPDATE alter_bill_mainsale
                SET activity = :activity";
        $params = [
            "activity" => $activity,
            "recordid" => $itemRecordId,
        ];

        if ($infoId !== null) {
            $sql .= ", find_record_id = :info_id";
            $params["info_id"] = $infoId;
        }

        $sql .= " WHERE recordid = :recordid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    private function finalizeEventIfComplete(string $billNumber, int $alterTime): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM alter_bill_mainsale
             WHERE billnumber = :billnumber
               AND alter_time = :alter_time
               AND activity = 0",
        );
        $stmt->execute([
            "billnumber" => $billNumber,
            "alter_time" => $alterTime,
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $update = $this->db->prepare(
            "UPDATE alter_bill_billdata
             SET activity_update = 1
             WHERE billnumber = :billnumber
               AND alter_times = :alter_time",
        );
        $update->execute([
            "billnumber" => $billNumber,
            "alter_time" => $alterTime,
        ]);
    }

    private function restockReturnedItem(array $line, int $shopId, int $billedShop, int $returnQty, string $recordTime): void
    {
        $type = (int) ($line["type"] ?? 0);

        if ($type === 1) {
            $this->restockBarcodeItem($line, $shopId, $billedShop, $returnQty, $recordTime);
            return;
        }

        if ($type === 2) {
            $this->restockImeiItem($line, $shopId, $billedShop, $recordTime);
            return;
        }

        $this->restockRechargeItem($line, $shopId, $billedShop, $returnQty, $recordTime);
    }

    private function restockBarcodeItem(array $line, int $shopId, int $billedShop, int $returnQty, string $recordTime): void
    {
        $findCurrent = $this->db->prepare(
            "SELECT item_stock_id
             FROM shop_stock_item
             WHERE stock_in_shop = :shop_id
               AND item_cat_id = :cat_id
               AND item_name_id = :part_id
               AND item_cost_price = :cost_price
             ORDER BY item_stock_id DESC
             LIMIT 1
             FOR UPDATE",
        );
        $findCurrent->execute([
            "shop_id" => $shopId,
            "cat_id" => (int) ($line["cat_id"] ?? 0),
            "part_id" => (int) ($line["part_id"] ?? 0),
            "cost_price" => (float) ($line["cost"] ?? 0),
        ]);
        $currentId = $findCurrent->fetchColumn();

        if ($currentId !== false) {
            $update = $this->db->prepare(
                "UPDATE shop_stock_item
                 SET stock_current = stock_current + :qty
                 WHERE item_stock_id = :row_id",
            );
            $update->execute([
                "qty" => $returnQty,
                "row_id" => (int) $currentId,
            ]);
            return;
        }

        $clone = $this->db->prepare(
            "INSERT INTO shop_stock_item
             (grn_refno, gen_refno, supplier_id, valied_month, fixed_yy, gen_seq, stock_added, stock_current, stock_status, item_cat, item_name, item_cat_id, item_name_id, item_color, item_cost_price, item_sell_price, item_free, item_low_price, item_other_price, warranty_span, warranty_type, stock_in_shop, barcode_print, trans_uid, stock_add_dt)
             SELECT grn_refno, gen_refno, supplier_id, valied_month, fixed_yy, gen_seq, stock_added, :stock_current, 1, item_cat, item_name, item_cat_id, item_name_id, item_color, item_cost_price, item_sell_price, item_free, item_low_price, item_other_price, warranty_span, warranty_type, :target_shop, barcode_print, trans_uid, :stock_add_dt
             FROM shop_stock_item
             WHERE stock_in_shop = :billed_shop
               AND item_cat_id = :cat_id
               AND item_name_id = :part_id
               AND item_cost_price = :cost_price
             ORDER BY item_stock_id DESC
             LIMIT 1",
        );
        $clone->execute([
            "stock_current" => $returnQty,
            "target_shop" => $shopId,
            "stock_add_dt" => $recordTime,
            "billed_shop" => $billedShop,
            "cat_id" => (int) ($line["cat_id"] ?? 0),
            "part_id" => (int) ($line["part_id"] ?? 0),
            "cost_price" => (float) ($line["cost"] ?? 0),
        ]);

        if ($clone->rowCount() < 1) {
            throw new RuntimeException("Failed to restock returned barcode item.");
        }
    }

    private function restockImeiItem(array $line, int $shopId, int $billedShop, string $recordTime): void
    {
        $imei = (string) ($line["imei_part_no"] ?? "");

        $findCurrent = $this->db->prepare(
            "SELECT item_stock_id_imei
             FROM shop_stock_imei
             WHERE stock_in_shop = :shop_id
               AND imei_no = :imei
             ORDER BY item_stock_id_imei DESC
             LIMIT 1
             FOR UPDATE",
        );
        $findCurrent->execute([
            "shop_id" => $shopId,
            "imei" => $imei,
        ]);
        $currentId = $findCurrent->fetchColumn();

        if ($currentId !== false) {
            $update = $this->db->prepare(
                "UPDATE shop_stock_imei
                 SET stock_current = 1
                 WHERE item_stock_id_imei = :row_id",
            );
            $update->execute([
                "row_id" => (int) $currentId,
            ]);
            return;
        }

        $clone = $this->db->prepare(
            "INSERT INTO shop_stock_imei
             (grn_refno, supplier_id, stock_current, imei_no, item_cat, item_name, item_cat_id, item_name_id, item_color, item_cost_price, item_sell_price, item_free, item_low_price, item_other_price, warranty_span, warranty_type, stock_in_shop, stock_status, stock_add_dt)
             SELECT grn_refno, supplier_id, 1, imei_no, item_cat, item_name, item_cat_id, item_name_id, item_color, item_cost_price, item_sell_price, item_free, item_low_price, item_other_price, warranty_span, warranty_type, :target_shop, 1, :stock_add_dt
             FROM shop_stock_imei
             WHERE stock_in_shop = :billed_shop
               AND imei_no = :imei
             ORDER BY item_stock_id_imei DESC
             LIMIT 1",
        );
        $clone->execute([
            "target_shop" => $shopId,
            "stock_add_dt" => $recordTime,
            "billed_shop" => $billedShop,
            "imei" => $imei,
        ]);

        if ($clone->rowCount() < 1) {
            throw new RuntimeException("Failed to restock returned IMEI item.");
        }
    }

    private function restockRechargeItem(array $line, int $shopId, int $billedShop, int $returnQty, string $recordTime): void
    {
        $findCurrent = $this->db->prepare(
            "SELECT recordid
             FROM shop_rcv_stock
             WHERE stock_in_shop = :shop_id
               AND item_cat_id = :cat_id
               AND item_name_id = :part_id
             ORDER BY recordid DESC
             LIMIT 1
             FOR UPDATE",
        );
        $findCurrent->execute([
            "shop_id" => $shopId,
            "cat_id" => (int) ($line["cat_id"] ?? 0),
            "part_id" => (int) ($line["part_id"] ?? 0),
        ]);
        $currentId = $findCurrent->fetchColumn();

        if ($currentId !== false) {
            $update = $this->db->prepare(
                "UPDATE shop_rcv_stock
                 SET current_stock = current_stock + :qty
                 WHERE recordid = :row_id",
            );
            $update->execute([
                "qty" => $returnQty,
                "row_id" => (int) $currentId,
            ]);
            return;
        }

        $clone = $this->db->prepare(
            "INSERT INTO shop_rcv_stock
             (card_id, card_name, item_cat_id, item_name_id, current_stock, min_limit, stock_status, cost_price, sell_price, low_price, other_price, stock_in_shop, last_upd)
             SELECT card_id, card_name, item_cat_id, item_name_id, :current_stock, min_limit, 1, cost_price, sell_price, low_price, other_price, :target_shop, :last_upd
             FROM shop_rcv_stock
             WHERE stock_in_shop = :billed_shop
               AND item_cat_id = :cat_id
               AND item_name_id = :part_id
             ORDER BY recordid DESC
             LIMIT 1",
        );
        $clone->execute([
            "current_stock" => $returnQty,
            "target_shop" => $shopId,
            "last_upd" => $recordTime,
            "billed_shop" => $billedShop,
            "cat_id" => (int) ($line["cat_id"] ?? 0),
            "part_id" => (int) ($line["part_id"] ?? 0),
        ]);

        if ($clone->rowCount() < 1) {
            throw new RuntimeException("Failed to restock returned recharge item.");
        }
    }

    private function insertReturnLog(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO stock_return_log
             (return_type, billnumber, part_id, cat_id, type, item_name, imei_part_no, cost, qty, return_cost, stock_shop, operate_shop, operator, activity, finalized_user, finalized_time, create_time)
             VALUES
             (:return_type, :billnumber, :part_id, :cat_id, :type, :item_name, :imei_part_no, :cost, :qty, :return_cost, :stock_shop, :operate_shop, :operator, :activity, :finalized_user, :finalized_time, :create_time)",
        );
        $stmt->execute([
            "return_type" => $data["return_type"] ?? 0,
            "billnumber" => $data["billnumber"] ?? "",
            "part_id" => $data["part_id"] ?? 0,
            "cat_id" => $data["cat_id"] ?? 0,
            "type" => $data["type"] ?? 0,
            "item_name" => $data["item_name"] ?? "",
            "imei_part_no" => $data["imei_part_no"] ?? "",
            "cost" => $data["cost"] ?? 0,
            "qty" => $data["qty"] ?? 0,
            "return_cost" => $data["return_cost"] ?? 0,
            "stock_shop" => $data["stock_shop"] ?? 0,
            "operate_shop" => $data["operate_shop"] ?? 0,
            "operator" => $data["operator"] ?? 0,
            "activity" => $data["activity"] ?? 0,
            "finalized_user" => $data["finalized_user"] ?? null,
            "finalized_time" => $data["finalized_time"] ?? null,
            "create_time" => $data["create_time"] ?? date("Y-m-d H:i:s"),
        ]);
    }

    private function insertCashBook(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO cash_book
             (op_date, shop, user, pay_type, remark, open_balance, cash_in, cash_out, close_balance)
             VALUES
             (:op_date, :shop, :user, :pay_type, :remark, 0, :cash_in, :cash_out, 0)",
        );
        $stmt->execute($data);
    }
}
