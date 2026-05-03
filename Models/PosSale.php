<?php

declare(strict_types=1);

class PosSale extends Model
{
    protected $table = "shop_pos_billdetails";

    public function checkout(array $payload): string
    {
        $shopId = (int) $payload["shop_id"];
        $userId = (int) $payload["user_id"];
        $sellerId = (int) ($payload["seller_id"] ?? $userId);
        $customerId = (int) $payload["customer"]["id"];
        $customerName = (string) $payload["customer"]["name"];
        $lines = $payload["lines"];
        $payment = $payload["payment"];
        $summary = $payload["summary"];
        $shop = $payload["shop"];
        $recordTime = date("Y-m-d H:i:s");
        $billMonth = date("ym");
        $lockName = "pos_bill_seq_shop_" . $shopId;

        $lockStmt = $this->db->prepare("SELECT GET_LOCK(:lock_name, 10)");
        $lockStmt->execute(["lock_name" => $lockName]);

        if ((int) $lockStmt->fetchColumn() !== 1) {
            throw new RuntimeException("Failed to acquire POS bill lock.");
        }

        try {
            $this->db->beginTransaction();

            $sequence = $this->nextSequence($billMonth, $shopId);
            $billNumber = $this->buildBillNumber((string) ($shop["pos_uniq"] ?? ""), $billMonth, $sequence);

            $paymentType = $this->paymentType($payment);
            $cashTendered = (float) ($payment["cash_amount"] ?? 0);
            $cardTendered = (float) ($payment["card_amount"] ?? 0);
            $change = (float) ($summary["balance"] ?? 0);
            $applied = $this->appliedPayments($cashTendered, $cardTendered, (float) $summary["total"]);
            $dueAmount = max(0, (float) $summary["total"] - ($applied["cash"] + $applied["card"]));

            $insertBill = $this->db->prepare(
                "INSERT INTO shop_pos_billdetails
                 (bill_month, bill_seq, billnumber, billed_shop, customer_id, customer_name, printinfo, printbill_reason, totalbill, paytype, cash_pay, card_pay, card_number, balance, operator, seller_id, seller_name, alter_bill, complete, billaddedtime)
                 VALUES
                 (:bill_month, :bill_seq, :billnumber, :billed_shop, :customer_id, :customer_name, 0, '', :totalbill, :paytype, :cash_pay, :card_pay, :card_number, :balance, :operator, :seller_id, :seller_name, 0, 1, :billaddedtime)",
            );
            $insertBill->execute([
                "bill_month" => $billMonth,
                "bill_seq" => $sequence["raw"],
                "billnumber" => $billNumber,
                "billed_shop" => $shopId,
                "customer_id" => $customerId,
                "customer_name" => $customerName,
                "totalbill" => (float) $summary["total"],
                "paytype" => $paymentType,
                "cash_pay" => $cashTendered,
                "card_pay" => $cardTendered,
                "card_number" => (string) ($payment["card_number"] ?? ""),
                "balance" => $change,
                "operator" => $userId,
                "seller_id" => $sellerId,
                "seller_name" => (string) ($payload["seller_name"] ?? ""),
                "billaddedtime" => $recordTime,
            ]);

            $insertLine = $this->db->prepare(
                "INSERT INTO shop_pos_mainsale
                 (billnumber, part_id, cat_id, type, item_name, imei_part_no, cost, regular_price, qty, sale_price, discount, sub_total, waranty, record_time)
                 VALUES
                 (:billnumber, :part_id, :cat_id, :type, :item_name, :imei_part_no, :cost, :regular_price, :qty, :sale_price, :discount, :sub_total, :waranty, :record_time)",
            );

            foreach ($lines as $line) {
                $insertLine->execute([
                    "billnumber" => $billNumber,
                    "part_id" => (int) $line["item_id"],
                    "cat_id" => (int) $line["cat_id"],
                    "type" => (int) $line["type"],
                    "item_name" => (string) $line["item_name"],
                    "imei_part_no" => (string) $line["code"],
                    "cost" => (float) $line["cost_price"],
                    "regular_price" => (float) $line["sale_price"],
                    "qty" => (int) $line["qty"],
                    "sale_price" => (float) $line["sale_price"],
                    "discount" => (float) $line["discount"],
                    "sub_total" => (float) $line["sub_total"],
                    "waranty" => (string) $line["warranty"],
                    "record_time" => $recordTime,
                ]);

                $this->decrementStock($line);
            }

            if ($customerId > 0) {
                $this->recordCustomerLedger(
                    $billNumber,
                    $shopId,
                    $customerId,
                    $userId,
                    $recordTime,
                    (float) $summary["total"],
                    $applied["cash"],
                    $applied["card"],
                    $dueAmount,
                    (string) ($payment["card_number"] ?? ""),
                );
            }

            if ($applied["cash"] > 0) {
                $this->insertCashBook([
                    "op_date" => $recordTime,
                    "shop" => $shopId,
                    "user" => $userId,
                    "pay_type" => 1,
                    "remark" => "Cash payment for Bill " . $billNumber . " (by " . $customerName . ")",
                    "cash_in" => $applied["cash"],
                    "cash_out" => 0,
                ]);
            }

            if ($applied["card"] > 0) {
                $this->insertCashBook([
                    "op_date" => $recordTime,
                    "shop" => $shopId,
                    "user" => $userId,
                    "pay_type" => 2,
                    "remark" => "Card payment for Bill " . $billNumber . " by " . $customerName . " (Card Number: " . (string) ($payment["card_number"] ?? "") . ")",
                    "cash_in" => $applied["card"],
                    "cash_out" => 0,
                ]);
            }

            $this->db->commit();
            $this->releaseLock($lockName);

            return $billNumber;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            $this->releaseLock($lockName);
            throw $exception;
        }
    }

    public function receipt(string $billNumber): ?array
    {
        $billStmt = $this->db->prepare(
            "SELECT * FROM shop_pos_billdetails WHERE billnumber = :billnumber LIMIT 1",
        );
        $billStmt->execute(["billnumber" => $billNumber]);
        $bill = $billStmt->fetch();

        if ($bill === false) {
            return null;
        }

        $lineStmt = $this->db->prepare(
            "SELECT * FROM shop_pos_mainsale WHERE billnumber = :billnumber ORDER BY recordid ASC",
        );
        $lineStmt->execute(["billnumber" => $billNumber]);
        $lines = $lineStmt->fetchAll();

        return [
            "bill" => $bill,
            "lines" => $lines,
        ];
    }

    public function dailyBills(int $shopId, int $operatorId, string $date): array
    {
        $sql = "SELECT *
                FROM shop_pos_billdetails
                WHERE complete = 1
                  AND DATE(billaddedtime) = :bill_date";
        $params = [
            "bill_date" => $date,
        ];

        if ($operatorId > 0) {
            $sql .= " AND operator = :operator";
            $params["operator"] = $operatorId;
        }

        if ($shopId > 0) {
            $sql .= " AND billed_shop = :shop_id";
            $params["shop_id"] = $shopId;
        }

        $sql .= " ORDER BY recordid DESC LIMIT 20";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $bills = $stmt->fetchAll();

        return $this->attachLines($bills);
    }

    public function searchBills(int $shopId, array $filters): array
    {
        $billNumber = trim((string) ($filters["billnumber"] ?? ""));
        $customerName = trim((string) ($filters["customer_name"] ?? ""));
        $itemName = trim((string) ($filters["item_name"] ?? ""));
        $itemCode = trim((string) ($filters["item_code"] ?? ""));
        $fromDate = trim((string) ($filters["from_date"] ?? ""));
        $toDate = trim((string) ($filters["to_date"] ?? ""));

        if (
            $billNumber === ""
            && $customerName === ""
            && $itemName === ""
            && $itemCode === ""
            && $fromDate === ""
            && $toDate === ""
        ) {
            return [];
        }

        if ($billNumber !== "" || $customerName !== "") {
            $sql = "SELECT *
                    FROM shop_pos_billdetails
                    WHERE complete = 1
                      AND billnumber LIKE :billnumber
                      AND customer_name LIKE :customer_name";
            $params = [
                "billnumber" => "%" . $billNumber . "%",
                "customer_name" => "%" . $customerName . "%",
            ];

            if ($fromDate !== "") {
                $sql .= " AND DATE(billaddedtime) >= :from_date";
                $params["from_date"] = $fromDate;
            }

            if ($toDate !== "") {
                $sql .= " AND DATE(billaddedtime) <= :to_date";
                $params["to_date"] = $toDate;
            }

            if ($shopId > 0) {
                $sql .= " AND billed_shop = :shop_id";
                $params["shop_id"] = $shopId;
            }

            $sql .= " ORDER BY recordid DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $this->attachLines($stmt->fetchAll());
        }

        $sql = "SELECT DISTINCT sale.billnumber
                FROM shop_pos_mainsale sale
                INNER JOIN shop_pos_billdetails bill ON bill.billnumber = sale.billnumber
                WHERE bill.complete = 1
                  AND sale.item_name LIKE :item_name
                  AND sale.imei_part_no LIKE :item_code";
        $params = [
            "item_name" => "%" . $itemName . "%",
            "item_code" => "%" . $itemCode . "%",
        ];

        if ($fromDate !== "") {
            $sql .= " AND DATE(sale.record_time) >= :from_date";
            $params["from_date"] = $fromDate;
        }

        if ($toDate !== "") {
            $sql .= " AND DATE(sale.record_time) <= :to_date";
            $params["to_date"] = $toDate;
        }

        if ($shopId > 0) {
            $sql .= " AND bill.billed_shop = :shop_id";
            $params["shop_id"] = $shopId;
        }

        $sql .= " ORDER BY sale.billnumber DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $billNumbers = array_values(array_filter(array_map(
            static fn (mixed $row): string => (string) ($row["billnumber"] ?? ""),
            $stmt->fetchAll(),
        )));

        if ($billNumbers === []) {
            return [];
        }

        return $this->billsByNumbers($billNumbers, $shopId);
    }

    public function barcodeLabels(string $billNumber): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                sale.recordid,
                sale.billnumber,
                sale.part_id,
                sale.type,
                sale.item_name,
                sale.imei_part_no,
                sale.qty,
                sale.waranty,
                CASE
                    WHEN sale.type = 1 THEN COALESCE(stock.supplier_id, '')
                    ELSE ''
                END AS supplier_id
             FROM shop_pos_mainsale sale
             LEFT JOIN (
                SELECT gen_refno, MAX(supplier_id) AS supplier_id
                FROM shop_stock_item
                GROUP BY gen_refno
             ) stock ON stock.gen_refno = sale.imei_part_no
             WHERE sale.billnumber = :billnumber
             ORDER BY sale.recordid ASC",
        );
        $stmt->execute(["billnumber" => $billNumber]);

        return $stmt->fetchAll();
    }

    public function cancelBill(string $billNumber, int $shopId, int $userId, string $username, string $reason): void
    {
        $recordTime = date("Y-m-d H:i:s");
        $reason = trim($reason);

        if ($reason === "") {
            throw new RuntimeException("Cancel reason is required.");
        }

        $this->db->beginTransaction();

        try {
            $billStmt = $this->db->prepare(
                "SELECT *
                 FROM shop_pos_billdetails
                 WHERE billnumber = :billnumber
                   AND billed_shop = :shop_id
                   AND operator = :operator
                   AND complete = 1
                 LIMIT 1
                 FOR UPDATE",
            );
            $billStmt->execute([
                "billnumber" => $billNumber,
                "shop_id" => $shopId,
                "operator" => $userId,
            ]);
            $bill = $billStmt->fetch();

            if ($bill === false) {
                throw new RuntimeException("Bill was not found for cancellation under the current cashier.");
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
            $lines = $lineStmt->fetchAll();

            $this->archiveCancelledBill($bill, $lines, $userId, $username, $reason, $recordTime);

            foreach ($lines as $line) {
                $this->restoreStock($line, $shopId);
            }

            $customerId = (int) ($bill["customer_id"] ?? 0);
            $cashApplied = (float) ($bill["cash_pay"] ?? 0);
            $cardApplied = (float) ($bill["card_pay"] ?? 0);

            if ($customerId > 0) {
                $dueAmount = $this->removeCustomerLedger($billNumber, $customerId);
                if ($dueAmount > 0) {
                    $updateCustomer = $this->db->prepare(
                        "UPDATE shop_customer
                         SET accbalance = GREATEST(COALESCE(accbalance, 0) - :due_amount, 0)
                         WHERE recordid = :customer_id",
                    );
                    $updateCustomer->execute([
                        "due_amount" => $dueAmount,
                        "customer_id" => $customerId,
                    ]);
                }
            } else {
                $this->removeBillPaymentLogs($billNumber);
            }

            if ($cashApplied > 0) {
                $this->insertCashBook([
                    "op_date" => $recordTime,
                    "shop" => $shopId,
                    "user" => $userId,
                    "pay_type" => 1,
                    "remark" => "Cancel Bill ID " . $billNumber . " issued for " . (string) ($bill["customer_name"] ?? "Cash Customer"),
                    "cash_in" => 0,
                    "cash_out" => $cashApplied,
                ]);
            }

            if ($cardApplied > 0) {
                $this->insertCashBook([
                    "op_date" => $recordTime,
                    "shop" => $shopId,
                    "user" => $userId,
                    "pay_type" => 2,
                    "remark" => "Cancel Bill ID " . $billNumber . " issued for " . (string) ($bill["customer_name"] ?? "Cash Customer"),
                    "cash_in" => 0,
                    "cash_out" => $cardApplied,
                ]);
            }

            $deleteBill = $this->db->prepare(
                "DELETE FROM shop_pos_billdetails WHERE billnumber = :billnumber",
            );
            $deleteBill->execute([
                "billnumber" => $billNumber,
            ]);

            $deleteLines = $this->db->prepare(
                "DELETE FROM shop_pos_mainsale WHERE billnumber = :billnumber",
            );
            $deleteLines->execute([
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

    private function billsByNumbers(array $billNumbers, int $shopId): array
    {
        $placeholders = [];
        $params = [];

        foreach ($billNumbers as $index => $billNumber) {
            $key = "bill_" . $index;
            $placeholders[] = ":" . $key;
            $params[$key] = $billNumber;
        }

        $sql = "SELECT *
                FROM shop_pos_billdetails
                WHERE complete = 1
                  AND billnumber IN (" . implode(", ", $placeholders) . ")";

        if ($shopId > 0) {
            $sql .= " AND billed_shop = :shop_id";
            $params["shop_id"] = $shopId;
        }

        $sql .= " ORDER BY recordid DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->attachLines($stmt->fetchAll());
    }

    private function archiveCancelledBill(array $bill, array $lines, int $userId, string $username, string $reason, string $recordTime): void
    {
        $archiveBill = $this->db->prepare(
            "INSERT INTO cancel_bill_billdetails
             (recordid, bill_month, bill_seq, billnumber, billed_shop, billaddedtime, customer_id, customer_name, printinfo, printbill_reason, totalbill, paytype, cash_pay, card_pay, card_number, chq_pay, chq_number, balance, operator, seller_id, seller_name, alter_bill, complete, cancel_userid, cancel_username, cancel_reason, cancel_process, cancel_time)
             VALUES
             (:recordid, :bill_month, :bill_seq, :billnumber, :billed_shop, :billaddedtime, :customer_id, :customer_name, :printinfo, :printbill_reason, :totalbill, :paytype, :cash_pay, :card_pay, :card_number, :chq_pay, :chq_number, :balance, :operator, :seller_id, :seller_name, :alter_bill, :complete, :cancel_userid, :cancel_username, :cancel_reason, 1, :cancel_time)",
        );
        $archiveBill->execute([
            "recordid" => $bill["recordid"] ?? null,
            "bill_month" => $bill["bill_month"] ?? null,
            "bill_seq" => $bill["bill_seq"] ?? null,
            "billnumber" => $bill["billnumber"] ?? null,
            "billed_shop" => $bill["billed_shop"] ?? null,
            "billaddedtime" => $bill["billaddedtime"] ?? null,
            "customer_id" => $bill["customer_id"] ?? null,
            "customer_name" => $bill["customer_name"] ?? null,
            "printinfo" => $bill["printinfo"] ?? 0,
            "printbill_reason" => $bill["printbill_reason"] ?? "",
            "totalbill" => $bill["totalbill"] ?? 0,
            "paytype" => $bill["paytype"] ?? 0,
            "cash_pay" => $bill["cash_pay"] ?? 0,
            "card_pay" => $bill["card_pay"] ?? 0,
            "card_number" => $bill["card_number"] ?? "",
            "chq_pay" => $bill["chq_pay"] ?? 0,
            "chq_number" => $bill["chq_number"] ?? "",
            "balance" => $bill["balance"] ?? 0,
            "operator" => $bill["operator"] ?? 0,
            "seller_id" => $bill["seller_id"] ?? 0,
            "seller_name" => $bill["seller_name"] ?? "",
            "alter_bill" => $bill["alter_bill"] ?? 0,
            "complete" => $bill["complete"] ?? 1,
            "cancel_userid" => $userId,
            "cancel_username" => $username,
            "cancel_reason" => $reason,
            "cancel_time" => $recordTime,
        ]);

        $archiveLine = $this->db->prepare(
            "INSERT INTO cancel_bill_mainsale
             (recordid, billnumber, part_id, cat_id, type, item_name, imei_part_no, cost, regular_price, qty, sale_price, discount, sub_total, waranty, record_time)
             VALUES
             (:recordid, :billnumber, :part_id, :cat_id, :type, :item_name, :imei_part_no, :cost, :regular_price, :qty, :sale_price, :discount, :sub_total, :waranty, :record_time)",
        );

        foreach ($lines as $line) {
            $archiveLine->execute([
                "recordid" => $line["recordid"] ?? null,
                "billnumber" => $line["billnumber"] ?? null,
                "part_id" => $line["part_id"] ?? 0,
                "cat_id" => $line["cat_id"] ?? 0,
                "type" => $line["type"] ?? 0,
                "item_name" => $line["item_name"] ?? "",
                "imei_part_no" => $line["imei_part_no"] ?? "",
                "cost" => $line["cost"] ?? 0,
                "regular_price" => $line["regular_price"] ?? 0,
                "qty" => $line["qty"] ?? 0,
                "sale_price" => $line["sale_price"] ?? 0,
                "discount" => $line["discount"] ?? 0,
                "sub_total" => $line["sub_total"] ?? 0,
                "waranty" => $line["waranty"] ?? "",
                "record_time" => $line["record_time"] ?? $recordTime,
            ]);
        }
    }

    private function attachLines(array $bills): array
    {
        if ($bills === []) {
            return [];
        }

        $lineStmt = $this->db->prepare(
            "SELECT * FROM shop_pos_mainsale WHERE billnumber = :billnumber ORDER BY recordid ASC",
        );

        foreach ($bills as &$bill) {
            $lineStmt->execute([
                "billnumber" => (string) ($bill["billnumber"] ?? ""),
            ]);
            $bill["lines"] = $lineStmt->fetchAll();
        }
        unset($bill);

        return $bills;
    }

    private function nextSequence(string $billMonth, int $shopId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(bill_seq), 0)
             FROM shop_pos_billdetails
             WHERE bill_month = :bill_month
               AND billed_shop = :billed_shop",
        );
        $stmt->execute([
            "bill_month" => $billMonth,
            "billed_shop" => $shopId,
        ]);

        $raw = ((int) $stmt->fetchColumn()) + 1;

        return [
            "raw" => $raw,
            "padded" => str_pad((string) $raw, 4, "0", STR_PAD_LEFT),
        ];
    }

    private function buildBillNumber(string $shopUnique, string $billMonth, array $sequence): string
    {
        if ($shopUnique === "") {
            throw new RuntimeException("Shop POS unique prefix is not configured.");
        }

        return $shopUnique . $billMonth . $sequence["padded"] . random_int(10, 99);
    }

    private function paymentType(array $payment): int
    {
        return match ((string) ($payment["method"] ?? "cash")) {
            "card" => 2,
            "split" => 3,
            default => 1,
        };
    }

    private function appliedPayments(float $cashTendered, float $cardTendered, float $total): array
    {
        $cashApplied = $cashTendered;
        $cardApplied = $cardTendered;
        $overpay = max(0, ($cashTendered + $cardTendered) - $total);

        if ($overpay > 0) {
            $deductCash = min($cashApplied, $overpay);
            $cashApplied -= $deductCash;
            $overpay -= $deductCash;
        }

        if ($overpay > 0) {
            $deductCard = min($cardApplied, $overpay);
            $cardApplied -= $deductCard;
        }

        return [
            "cash" => max(0, $cashApplied),
            "card" => max(0, $cardApplied),
        ];
    }

    private function decrementStock(array $line): void
    {
        $type = (int) $line["type"];
        $qty = (int) $line["qty"];
        $rowId = (int) $line["row_id"];

        if ($type === 1) {
            $select = $this->db->prepare(
                "SELECT stock_current FROM shop_stock_item WHERE item_stock_id = :row_id FOR UPDATE",
            );
            $select->execute(["row_id" => $rowId]);
            $current = $select->fetchColumn();

            if ($current === false || (int) $current < $qty) {
                throw new RuntimeException("Insufficient barcode stock for " . (string) $line["item_name"] . ".");
            }

            $update = $this->db->prepare(
                "UPDATE shop_stock_item
                 SET stock_current = stock_current - :qty
                 WHERE item_stock_id = :row_id",
            );
            $update->execute([
                "qty" => $qty,
                "row_id" => $rowId,
            ]);
            return;
        }

        if ($type === 2) {
            $select = $this->db->prepare(
                "SELECT stock_current FROM shop_stock_imei WHERE item_stock_id_imei = :row_id FOR UPDATE",
            );
            $select->execute(["row_id" => $rowId]);
            $current = $select->fetchColumn();

            if ($current === false || (int) $current < $qty) {
                throw new RuntimeException("Insufficient IMEI stock for " . (string) $line["item_name"] . ".");
            }

            $update = $this->db->prepare(
                "UPDATE shop_stock_imei
                 SET stock_current = stock_current - :qty
                 WHERE item_stock_id_imei = :row_id",
            );
            $update->execute([
                "qty" => $qty,
                "row_id" => $rowId,
            ]);
            return;
        }

        $select = $this->db->prepare(
            "SELECT current_stock FROM shop_rcv_stock WHERE recordid = :row_id FOR UPDATE",
        );
        $select->execute(["row_id" => $rowId]);
        $current = $select->fetchColumn();

        if ($current === false || (int) $current < $qty) {
            throw new RuntimeException("Insufficient recharge stock for " . (string) $line["item_name"] . ".");
        }

        $update = $this->db->prepare(
            "UPDATE shop_rcv_stock
             SET current_stock = current_stock - :qty
             WHERE recordid = :row_id",
        );
        $update->execute([
            "qty" => $qty,
            "row_id" => $rowId,
        ]);
    }

    private function restoreStock(array $line, int $shopId): void
    {
        $type = (int) ($line["type"] ?? 0);
        $qty = (int) ($line["qty"] ?? 0);
        $partId = (int) ($line["part_id"] ?? 0);
        $catId = (int) ($line["cat_id"] ?? 0);
        $code = (string) ($line["imei_part_no"] ?? "");

        if ($type === 1) {
            $select = $this->db->prepare(
                "SELECT item_stock_id
                 FROM shop_stock_item
                 WHERE gen_refno = :code
                   AND stock_in_shop = :shop_id
                   AND stock_status = 1
                 ORDER BY item_stock_id ASC
                 LIMIT 1
                 FOR UPDATE",
            );
            $select->execute([
                "code" => $code,
                "shop_id" => $shopId,
            ]);
            $rowId = $select->fetchColumn();

            if ($rowId === false) {
                throw new RuntimeException("Failed to restore barcode stock for cancelled bill item " . (string) ($line["item_name"] ?? "") . ".");
            }

            $update = $this->db->prepare(
                "UPDATE shop_stock_item
                 SET stock_current = stock_current + :qty
                 WHERE item_stock_id = :row_id",
            );
            $update->execute([
                "qty" => $qty,
                "row_id" => (int) $rowId,
            ]);
            return;
        }

        if ($type === 2) {
            $select = $this->db->prepare(
                "SELECT item_stock_id_imei
                 FROM shop_stock_imei
                 WHERE imei_no = :code
                   AND stock_in_shop = :shop_id
                   AND stock_status = 1
                 ORDER BY item_stock_id_imei ASC
                 LIMIT 1
                 FOR UPDATE",
            );
            $select->execute([
                "code" => $code,
                "shop_id" => $shopId,
            ]);
            $rowId = $select->fetchColumn();

            if ($rowId === false) {
                throw new RuntimeException("Failed to restore IMEI stock for cancelled bill item " . (string) ($line["item_name"] ?? "") . ".");
            }

            $update = $this->db->prepare(
                "UPDATE shop_stock_imei
                 SET stock_current = 1
                 WHERE item_stock_id_imei = :row_id",
            );
            $update->execute([
                "row_id" => (int) $rowId,
            ]);
            return;
        }

        $select = $this->db->prepare(
            "SELECT recordid
             FROM shop_rcv_stock
             WHERE item_cat_id = :cat_id
               AND item_name_id = :part_id
               AND stock_in_shop = :shop_id
               AND stock_status = 1
             ORDER BY recordid ASC
             LIMIT 1
             FOR UPDATE",
        );
        $select->execute([
            "cat_id" => $catId,
            "part_id" => $partId,
            "shop_id" => $shopId,
        ]);
        $rowId = $select->fetchColumn();

        if ($rowId === false) {
            throw new RuntimeException("Failed to restore recharge stock for cancelled bill item " . (string) ($line["item_name"] ?? "") . ".");
        }

        $update = $this->db->prepare(
            "UPDATE shop_rcv_stock
             SET current_stock = current_stock + :qty
             WHERE recordid = :row_id",
        );
        $update->execute([
            "qty" => $qty,
            "row_id" => (int) $rowId,
        ]);
    }

    private function recordCustomerLedger(
        string $billNumber,
        int $shopId,
        int $customerId,
        int $userId,
        string $recordTime,
        float $total,
        float $cashApplied,
        float $cardApplied,
        float $dueAmount,
        string $cardNumber,
    ): void {
        $insertAccount = $this->db->prepare(
            "INSERT INTO account_customer
             (recordtime, customer, operator, op_type, details, paytype, debit, credit)
             VALUES
             (:recordtime, :customer, :operator, :op_type, :details, :paytype, :debit, :credit)",
        );

        $insertAccount->execute([
            "recordtime" => $recordTime,
            "customer" => $customerId,
            "operator" => $userId,
            "op_type" => 7,
            "details" => "Total Amount need to pay for Bill ID " . $billNumber,
            "paytype" => 1,
            "debit" => $total,
            "credit" => 0,
        ]);

        $paymentStatus = $dueAmount > 0 ? 1 : 0;
        $payStmt = $this->db->prepare(
            "INSERT INTO shop_bill_pay
             (billnumber, billed_shop, customer_id, payment_status, totalbill, cash_pay, card_pay, due_amount, operator, complete_time, billaddedtime)
             VALUES
             (:billnumber, :billed_shop, :customer_id, :payment_status, :totalbill, :cash_pay, :card_pay, :due_amount, :operator, :complete_time, :billaddedtime)",
        );
        $payStmt->execute([
            "billnumber" => $billNumber,
            "billed_shop" => $shopId,
            "customer_id" => $customerId,
            "payment_status" => $paymentStatus,
            "totalbill" => $total,
            "cash_pay" => $cashApplied,
            "card_pay" => $cardApplied,
            "due_amount" => $dueAmount,
            "operator" => $userId,
            "complete_time" => $paymentStatus === 0 ? $recordTime : null,
            "billaddedtime" => $recordTime,
        ]);

        $payLog = $this->db->prepare(
            "INSERT INTO shop_bill_pay_log
             (record_time, bill_number, bill_final_amount, pay_type, cash_pay_amount, card_pay_amount, collect_user)
             VALUES
             (:record_time, :bill_number, :bill_final_amount, :pay_type, :cash_pay_amount, :card_pay_amount, :collect_user)",
        );
        $payLog->execute([
            "record_time" => $recordTime,
            "bill_number" => $billNumber,
            "bill_final_amount" => $total,
            "pay_type" => $paymentStatus,
            "cash_pay_amount" => $cashApplied > 0 ? $cashApplied : 0,
            "card_pay_amount" => $cardApplied > 0 ? $cardApplied : 0,
            "collect_user" => $userId,
        ]);

        if ($cardApplied > 0) {
            $insertAccount->execute([
                "recordtime" => $recordTime,
                "customer" => $customerId,
                "operator" => $userId,
                "op_type" => 2,
                "details" => "Card Payment for Bill ID " . $billNumber . " (Card number:" . $cardNumber . ")",
                "paytype" => 1,
                "debit" => 0,
                "credit" => $cardApplied,
            ]);
        }

        if ($cashApplied > 0) {
            $insertAccount->execute([
                "recordtime" => $recordTime,
                "customer" => $customerId,
                "operator" => $userId,
                "op_type" => 1,
                "details" => "Cash Payment for Bill ID " . $billNumber,
                "paytype" => 1,
                "debit" => 0,
                "credit" => $cashApplied,
            ]);
        }

        if ($dueAmount > 0) {
            $updateCustomer = $this->db->prepare(
                "UPDATE shop_customer
                 SET accbalance = COALESCE(accbalance, 0) + :due_amount
                 WHERE recordid = :customer_id",
            );
            $updateCustomer->execute([
                "due_amount" => $dueAmount,
                "customer_id" => $customerId,
            ]);
        }
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

    private function removeCustomerLedger(string $billNumber, int $customerId): float
    {
        $dueStmt = $this->db->prepare(
            "SELECT COALESCE(MAX(due_amount), 0)
             FROM shop_bill_pay
             WHERE billnumber = :billnumber
               AND customer_id = :customer_id",
        );
        $dueStmt->execute([
            "billnumber" => $billNumber,
            "customer_id" => $customerId,
        ]);
        $dueAmount = (float) $dueStmt->fetchColumn();

        $deleteAccount = $this->db->prepare(
            "DELETE FROM account_customer
             WHERE customer = :customer_id
               AND details LIKE :bill_token
               AND op_type IN (1, 2, 7)",
        );
        $deleteAccount->execute([
            "customer_id" => $customerId,
            "bill_token" => "%" . $billNumber . "%",
        ]);

        $this->removeBillPaymentLogs($billNumber);

        return $dueAmount;
    }

    private function removeBillPaymentLogs(string $billNumber): void
    {
        $deletePay = $this->db->prepare(
            "DELETE FROM shop_bill_pay WHERE billnumber = :billnumber",
        );
        $deletePay->execute([
            "billnumber" => $billNumber,
        ]);

        $deletePayLog = $this->db->prepare(
            "DELETE FROM shop_bill_pay_log WHERE bill_number = :billnumber",
        );
        $deletePayLog->execute([
            "billnumber" => $billNumber,
        ]);
    }

    private function releaseLock(string $lockName): void
    {
        $stmt = $this->db->prepare("SELECT RELEASE_LOCK(:lock_name)");
        $stmt->execute(["lock_name" => $lockName]);
    }
}
