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

    private function releaseLock(string $lockName): void
    {
        $stmt = $this->db->prepare("SELECT RELEASE_LOCK(:lock_name)");
        $stmt->execute(["lock_name" => $lockName]);
    }
}
