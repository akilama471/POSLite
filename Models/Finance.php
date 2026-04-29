<?php

declare(strict_types=1);

class Finance extends Model
{
    protected $table = "cash_book";

    private const SUPPLIER_OP_TYPES = [
        1 => "GRN Pay - cash",
        2 => "GRN Pay - cheque",
        3 => "GRN Pay - Remain",
        4 => "Repay from supply pay - cash",
        5 => "Repay from supply pay - cheque",
        6 => "Repay from supply pay - cash credit",
        7 => "Billed Amount",
    ];

    public function supplierCredits(int $supplierId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM account_cashcredit
             WHERE supplier = :supplier
               AND status = 1
             ORDER BY logid DESC",
        );
        $stmt->execute(["supplier" => $supplierId]);

        return $stmt->fetchAll();
    }

    public function supplierPaymentHistory(int $supplierId, string $fromDate, string $toDate): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM account_supplier
             WHERE date(recordtime) >= :from_date
               AND date(recordtime) <= :to_date
               AND supplier = :supplier
             ORDER BY recordtime ASC, logid ASC",
        );
        $stmt->execute([
            "from_date" => $fromDate,
            "to_date" => $toDate,
            "supplier" => $supplierId,
        ]);

        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row["op_type_label"] = self::SUPPLIER_OP_TYPES[(int) ($row["op_type"] ?? 0)] ?? "Error";
        }

        return $rows;
    }

    public function customerPaymentHistory(int $customerId, string $fromDate, string $toDate): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM account_customer
             WHERE date(recordtime) >= :from_date
               AND date(recordtime) <= :to_date
               AND customer = :customer
             ORDER BY recordtime ASC, logid ASC",
        );
        $stmt->execute([
            "from_date" => $fromDate,
            "to_date" => $toDate,
            "customer" => $customerId,
        ]);

        return $stmt->fetchAll();
    }

    public function customerCredits(int $customerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM account_cashcredit_customer
             WHERE customer = :customer
               AND status = 1
             ORDER BY logid DESC",
        );
        $stmt->execute(["customer" => $customerId]);

        return $stmt->fetchAll();
    }

    public function refreshSupplierCashCreditBalances(): void
    {
        $suppliers = $this->db->query(
            "SELECT supplierid FROM shop_supplier WHERE supplier_status = 1 ORDER BY supplierid ASC",
        )->fetchAll();

        $balanceStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0)
             FROM account_cashcredit
             WHERE supplier = :supplier
               AND status = 1",
        );
        $updateStmt = $this->db->prepare(
            "UPDATE shop_supplier
             SET cash_credit_balance = :balance
             WHERE supplierid = :supplier",
        );

        foreach ($suppliers as $supplier) {
            $supplierId = (int) $supplier["supplierid"];
            $balanceStmt->execute(["supplier" => $supplierId]);
            $balance = (float) $balanceStmt->fetchColumn();
            $updateStmt->execute([
                "balance" => $balance,
                "supplier" => $supplierId,
            ]);
        }
    }

    public function refreshCustomerCashCreditBalances(): void
    {
        $customers = $this->db->query(
            "SELECT recordid FROM shop_customer WHERE recordid > 0 ORDER BY recordid ASC",
        )->fetchAll();

        $balanceStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0)
             FROM account_cashcredit_customer
             WHERE customer = :customer
               AND status = 1",
        );
        $updateStmt = $this->db->prepare(
            "UPDATE shop_customer
             SET cash_credit_balance = :balance
             WHERE recordid = :customer",
        );

        foreach ($customers as $customer) {
            $customerId = (int) $customer["recordid"];
            $balanceStmt->execute(["customer" => $customerId]);
            $balance = (float) $balanceStmt->fetchColumn();
            $updateStmt->execute([
                "balance" => $balance,
                "customer" => $customerId,
            ]);
        }
    }

    public function createSupplierPayment(array $payload): array
    {
        $this->db->beginTransaction();

        try {
            $amount = 0.0;

            if ($payload["method"] === "cash") {
                $amount = (float) $payload["amount"];

                $stmt = $this->db->prepare(
                    "INSERT INTO account_supplier
                    (recordtime, supplier, operator, op_type, details, paytype, debit, credit)
                    VALUES
                    (:recordtime, :supplier, :operator, 4, :details, 1, :debit, 0)",
                );
                $stmt->execute([
                    "recordtime" => $payload["recordtime"],
                    "supplier" => $payload["supplier_id"],
                    "operator" => $payload["user_id"],
                    "details" => "Cash Payment for supplier. " . $payload["reason"],
                    "debit" => $amount,
                ]);

                $this->insertCashBook([
                    "op_date" => $payload["recordtime"],
                    "shop" => $payload["shop_id"],
                    "user" => $payload["user_id"],
                    "pay_type" => 1,
                    "remark" => "Cash Payment for supplier : " . $payload["name"],
                    "cash_in" => 0,
                    "cash_out" => $amount,
                ]);
            } elseif ($payload["method"] === "cheque") {
                $amount = (float) $payload["amount"];

                $stmt = $this->db->prepare(
                    "INSERT INTO account_supplier
                    (recordtime, supplier, operator, op_type, details, paytype, debit, credit)
                    VALUES
                    (:recordtime, :supplier, :operator, 5, :details, 2, :debit, 0)",
                );
                $stmt->execute([
                    "recordtime" => $payload["recordtime"],
                    "supplier" => $payload["supplier_id"],
                    "operator" => $payload["user_id"],
                    "details" => "Cheque Payment for supplier. " . $payload["reason"],
                    "debit" => $amount,
                ]);

                $this->insertCheque([
                    "type" => 1,
                    "cheque_number" => $payload["cheque_number"],
                    "remark" => $payload["reason"],
                    "cheque_value" => $amount,
                    "cheque_date" => $payload["cheque_date"],
                    "reminder" => $payload["reminder"],
                    "reminder_date" => $payload["reminder_date"],
                    "record_date" => $payload["recordtime"],
                ]);

                $this->insertCashBook([
                    "op_date" => $payload["recordtime"],
                    "shop" => $payload["shop_id"],
                    "user" => $payload["user_id"],
                    "pay_type" => 3,
                    "remark" => "Cheque Payment for supplier : " . $payload["name"],
                    "cash_in" => 0,
                    "cash_out" => $amount,
                ]);
            } else {
                $credits = $this->supplierCreditsByIds(
                    $payload["supplier_id"],
                    $payload["credit_ids"],
                );

                foreach ($credits as $credit) {
                    $creditAmount = (float) $credit["amount"];
                    $amount += $creditAmount;

                    $stmt = $this->db->prepare(
                        "INSERT INTO account_supplier
                        (recordtime, supplier, operator, op_type, details, paytype, debit, credit)
                        VALUES
                        (:recordtime, :supplier, :operator, 6, :details, 1, :debit, 0)",
                    );
                    $stmt->execute([
                        "recordtime" => $payload["recordtime"],
                        "supplier" => $payload["supplier_id"],
                        "operator" => $payload["user_id"],
                        "details" => "Cash Credit Payment for supplier. " . $credit["remark"],
                        "debit" => $creditAmount,
                    ]);

                    $upd = $this->db->prepare(
                        "UPDATE account_cashcredit
                         SET status = 2, paiddate = NOW(), paieduser = :user
                         WHERE logid = :logid",
                    );
                    $upd->execute([
                        "user" => $payload["user_id"],
                        "logid" => $credit["logid"],
                    ]);
                }
            }

            $this->db->commit();

            return [
                "name" => $payload["name"],
                "method" => $payload["method"],
                "amount" => $amount,
                "reference" => $payload["cheque_number"] ?? null,
                "recordtime" => $payload["recordtime"],
                "direction" => "supplier",
            ];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function createCustomerPayment(array $payload): array
    {
        $this->db->beginTransaction();

        try {
            $amount = 0.0;

            if ($payload["method"] === "cash") {
                $amount = (float) $payload["amount"];

                $stmt = $this->db->prepare(
                    "INSERT INTO account_customer
                    (recordtime, customer, operator, op_type, details, paytype, debit, credit)
                    VALUES
                    (:recordtime, :customer, :operator, 4, :details, 1, 0, :credit)",
                );
                $stmt->execute([
                    "recordtime" => $payload["recordtime"],
                    "customer" => $payload["customer_id"],
                    "operator" => $payload["user_id"],
                    "details" => "Cash Payment from Customer. " . $payload["reason"],
                    "credit" => $amount,
                ]);

                $this->insertCashBook([
                    "op_date" => $payload["recordtime"],
                    "shop" => $payload["shop_id"],
                    "user" => $payload["user_id"],
                    "pay_type" => 1,
                    "remark" => "Cash Payment from Customer : " . $payload["name"],
                    "cash_in" => $amount,
                    "cash_out" => 0,
                ]);
            } elseif ($payload["method"] === "cheque") {
                $amount = (float) $payload["amount"];

                $stmt = $this->db->prepare(
                    "INSERT INTO account_customer
                    (recordtime, customer, operator, op_type, details, paytype, debit, credit)
                    VALUES
                    (:recordtime, :customer, :operator, 5, :details, 2, 0, :credit)",
                );
                $stmt->execute([
                    "recordtime" => $payload["recordtime"],
                    "customer" => $payload["customer_id"],
                    "operator" => $payload["user_id"],
                    "details" => "Cheque Payment from Customer. " . $payload["reason"],
                    "credit" => $amount,
                ]);

                $this->insertCheque([
                    "type" => 2,
                    "cheque_number" => $payload["cheque_number"],
                    "remark" => $payload["reason"],
                    "cheque_value" => $amount,
                    "cheque_date" => $payload["cheque_date"],
                    "reminder" => $payload["reminder"],
                    "reminder_date" => $payload["reminder_date"],
                    "record_date" => $payload["recordtime"],
                ]);

                $this->insertCashBook([
                    "op_date" => $payload["recordtime"],
                    "shop" => $payload["shop_id"],
                    "user" => $payload["user_id"],
                    "pay_type" => 3,
                    "remark" => "Cheque Payment from Customer : " . $payload["name"],
                    "cash_in" => $amount,
                    "cash_out" => 0,
                ]);
            } else {
                $credits = $this->customerCreditsByIds(
                    $payload["customer_id"],
                    $payload["credit_ids"],
                );

                foreach ($credits as $credit) {
                    $creditAmount = (float) $credit["amount"];
                    $amount += $creditAmount;

                    $stmt = $this->db->prepare(
                        "INSERT INTO account_customer
                        (recordtime, customer, operator, op_type, details, paytype, debit, credit)
                        VALUES
                        (:recordtime, :customer, :operator, 6, :details, 1, 0, :credit)",
                    );
                    $stmt->execute([
                        "recordtime" => $payload["recordtime"],
                        "customer" => $payload["customer_id"],
                        "operator" => $payload["user_id"],
                        "details" => "Cash Credit Payment for Customer. " . $credit["remark"],
                        "credit" => $creditAmount,
                    ]);

                    $upd = $this->db->prepare(
                        "UPDATE account_cashcredit_customer
                         SET status = 2, paiddate = NOW(), paieduser = :user
                         WHERE logid = :logid",
                    );
                    $upd->execute([
                        "user" => $payload["user_id"],
                        "logid" => $credit["logid"],
                    ]);
                }
            }

            $this->db->commit();

            return [
                "name" => $payload["name"],
                "method" => $payload["method"],
                "amount" => $amount,
                "reference" => $payload["cheque_number"] ?? null,
                "recordtime" => $payload["recordtime"],
                "direction" => "customer",
            ];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function supplierCreditsByIds(int $supplierId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        $params = array_merge([$supplierId], $ids);

        $stmt = $this->db->prepare(
            "SELECT * FROM account_cashcredit
             WHERE supplier = ?
               AND status = 1
               AND logid IN ($placeholders)
             ORDER BY logid DESC",
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function customerCreditsByIds(int $customerId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        $params = array_merge([$customerId], $ids);

        $stmt = $this->db->prepare(
            "SELECT * FROM account_cashcredit_customer
             WHERE customer = ?
               AND status = 1
               AND logid IN ($placeholders)
             ORDER BY logid DESC",
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function insertCheque(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO account_cheque
            (type, cheque_number, remark, cheque_value, cheque_date, reminder, reminder_date, record_date)
            VALUES
            (:type, :cheque_number, :remark, :cheque_value, :cheque_date, :reminder, :reminder_date, :record_date)",
        );
        $stmt->execute($data);
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
