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

    public function searchGrnPayments(array $filters, int $authShopId): array
    {
        $sql = "SELECT pay.*,
                       supplier.supplier_name,
                       shop.shop_info_name AS shop_name
                FROM shop_grn_pay AS pay
                LEFT JOIN shop_supplier AS supplier
                  ON supplier.supplierid = pay.supply_id
                LEFT JOIN sys_shop AS shop
                  ON shop.shopid = pay.shop_id
                WHERE 1 = 1";
        $params = [];

        if ($authShopId > 0) {
            $sql .= " AND pay.shop_id = :auth_shop_id";
            $params["auth_shop_id"] = $authShopId;
        } else {
            $shopId = (int) ($filters["shop_id"] ?? 0);
            if ($shopId > 0) {
                $sql .= " AND pay.shop_id = :shop_id";
                $params["shop_id"] = $shopId;
            }
        }

        $supplierId = (int) ($filters["supplier_id"] ?? 0);
        if ($supplierId > 0) {
            $sql .= " AND pay.supply_id = :supplier_id";
            $params["supplier_id"] = $supplierId;
        }

        $grnRef = trim((string) ($filters["grn_refno"] ?? ""));
        if ($grnRef !== "") {
            $sql .= " AND pay.grn_refno LIKE :grn_refno";
            $params["grn_refno"] = "%" . $grnRef . "%";
        }

        $status = (string) ($filters["payment_status"] ?? "due");
        if ($status === "due") {
            $sql .= " AND pay.payment_status = 1";
        } elseif ($status === "paid") {
            $sql .= " AND pay.payment_status = 0";
        }

        $fromDate = trim((string) ($filters["from_date"] ?? ""));
        $toDate = trim((string) ($filters["to_date"] ?? ""));
        if ($fromDate === "" && $toDate === "") {
            $fromDate = date("Y-m-d", strtotime("-1 month"));
        } elseif ($fromDate === "" && $toDate !== "") {
            $fromDate = $toDate;
        } elseif ($fromDate !== "" && $toDate === "") {
            $toDate = $fromDate;
        }

        if ($fromDate !== "") {
            $sql .= " AND date(pay.record_time) >= :from_date";
            $params["from_date"] = $fromDate;
        }

        if ($toDate !== "") {
            $sql .= " AND date(pay.record_time) <= :to_date";
            $params["to_date"] = $toDate;
        }

        $sql .= " ORDER BY pay.record_time DESC, pay.record_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findGrnPaymentById(int $recordId, int $authShopId): ?array
    {
        $sql = "SELECT pay.*,
                       supplier.supplier_name,
                       supplier.supplier_address,
                       supplier.supplier_mobile,
                       supplier.cash_credit_balance,
                       shop.shop_info_name AS shop_name
                FROM shop_grn_pay AS pay
                LEFT JOIN shop_supplier AS supplier
                  ON supplier.supplierid = pay.supply_id
                LEFT JOIN sys_shop AS shop
                  ON shop.shopid = pay.shop_id
                WHERE pay.record_id = :record_id";
        $params = ["record_id" => $recordId];

        if ($authShopId > 0) {
            $sql .= " AND pay.shop_id = :shop_id";
            $params["shop_id"] = $authShopId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $payment = $stmt->fetch();

        return $payment !== false ? $payment : null;
    }

    public function grnPaymentHistory(string $grnCode, int $authShopId): array
    {
        $sql = "SELECT log.*,
                       user.visibledata AS paid_user_name
                FROM shop_grn_pay_log AS log
                LEFT JOIN shop_grn_pay AS pay
                  ON pay.grn_refno = log.grn_code
                LEFT JOIN sys_user AS user
                  ON user.myid = log.paid_user
                WHERE log.grn_code = :grn_code";
        $params = ["grn_code" => $grnCode];

        if ($authShopId > 0) {
            $sql .= " AND pay.shop_id = :shop_id";
            $params["shop_id"] = $authShopId;
        }

        $sql .= " ORDER BY log.record_time DESC, log.log_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $type = (int) ($row["pay_type"] ?? 0);
            $row["pay_type_label"] = $type === 0
                ? "GRN Pay"
                : ($type === 1 ? "Remain Pay" : ($type === 2 ? "Remain Pay CC" : "Other"));
        }
        unset($row);

        return $rows;
    }

    public function settleGrnDueCash(int $recordId, array $payload, int $authShopId): array
    {
        return $this->settleGrnDue($recordId, $payload, $authShopId, "cash");
    }

    public function settleGrnDueCheque(int $recordId, array $payload, int $authShopId): array
    {
        return $this->settleGrnDue($recordId, $payload, $authShopId, "cheque");
    }

    public function settleGrnDueCredit(int $recordId, array $payload, int $authShopId): array
    {
        return $this->settleGrnDue($recordId, $payload, $authShopId, "credit");
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

    private function settleGrnDue(int $recordId, array $payload, int $authShopId, string $method): array
    {
        $payment = $this->findGrnPaymentById($recordId, $authShopId);
        if ($payment === null) {
            throw new RuntimeException("GRN payment record was not found.");
        }

        $dueAmount = (float) ($payment["due_amount"] ?? 0);
        if ($dueAmount <= 0) {
            throw new RuntimeException("There is no due amount for this GRN.");
        }

        $this->db->beginTransaction();

        try {
            $recordTime = (string) $payload["recordtime"];
            $userId = (int) $payload["user_id"];
            $shopId = (int) ($payment["shop_id"] ?? 0);
            $supplierId = (int) ($payment["supply_id"] ?? 0);
            $grnCode = (string) ($payment["grn_refno"] ?? "");
            $grnFinalAmount = (float) ($payment["grn_final_amount"] ?? 0);
            $cashTotal = (float) ($payment["cash_pay_amount"] ?? 0);
            $chequeTotal = (float) ($payment["chq_pay_amount"] ?? 0);
            $appliedAmount = 0.0;
            $payLogType = 1;

            if ($method === "cash") {
                $appliedAmount = round((float) ($payload["amount"] ?? 0), 2);
                if ($appliedAmount <= 0 || $appliedAmount > $dueAmount) {
                    throw new RuntimeException("Cash payment amount must be greater than zero and not exceed the due amount.");
                }

                $cashTotal += $appliedAmount;

                $remark = "Cash Payment for GRN ID " . $grnCode;
                $this->insertSupplierLedgerEntry($recordTime, $supplierId, $userId, 4, $remark, 1, $appliedAmount);
                $this->insertCashBook([
                    "op_date" => $recordTime,
                    "shop" => $shopId,
                    "user" => $userId,
                    "pay_type" => 1,
                    "remark" => $remark,
                    "cash_in" => 0,
                    "cash_out" => $appliedAmount,
                ]);
            } elseif ($method === "cheque") {
                $appliedAmount = round((float) ($payload["amount"] ?? 0), 2);
                $chequeNumber = trim((string) ($payload["cheque_number"] ?? ""));
                $chequeDate = trim((string) ($payload["cheque_date"] ?? ""));
                if ($appliedAmount <= 0 || $appliedAmount > $dueAmount || $chequeNumber === "" || $chequeDate === "") {
                    throw new RuntimeException("Cheque amount, number, and date are required, and the amount cannot exceed the due amount.");
                }

                $chequeTotal += $appliedAmount;

                $remark = "Cheque Payment for GRN ID " . $grnCode . ". Cheque number :" . $chequeNumber;
                $this->insertSupplierLedgerEntry($recordTime, $supplierId, $userId, 5, $remark, 2, $appliedAmount);
                $this->insertCashBook([
                    "op_date" => $recordTime,
                    "shop" => $shopId,
                    "user" => $userId,
                    "pay_type" => 3,
                    "remark" => $remark,
                    "cash_in" => 0,
                    "cash_out" => $appliedAmount,
                ]);
                $this->insertCheque([
                    "type" => 1,
                    "cheque_number" => $chequeNumber,
                    "remark" => $remark,
                    "cheque_value" => $appliedAmount,
                    "cheque_date" => $chequeDate,
                    "reminder" => (int) ($payload["reminder"] ?? 0),
                    "reminder_date" => trim((string) ($payload["reminder_date"] ?? "")) ?: null,
                    "record_date" => $recordTime,
                ]);
            } else {
                $creditIds = $payload["credit_ids"] ?? [];
                if (!is_array($creditIds) || $creditIds === []) {
                    throw new RuntimeException("Select at least one supplier cash-credit record.");
                }

                $credits = $this->supplierCreditsByIds($supplierId, array_map("intval", $creditIds));
                if ($credits === []) {
                    throw new RuntimeException("Selected cash-credit records were not found.");
                }

                $appliedAmount = round(array_reduce($credits, static function (float $carry, array $credit): float {
                    return $carry + (float) ($credit["amount"] ?? 0);
                }, 0.0), 2);

                if ($appliedAmount <= 0 || $appliedAmount > $dueAmount) {
                    throw new RuntimeException("Selected cash-credit total must be greater than zero and not exceed the due amount.");
                }

                $cashTotal += $appliedAmount;
                $payLogType = 2;

                foreach ($credits as $credit) {
                    $remark = "Cash Credit Payment for GRN ID " . $grnCode . ". (" . (string) ($credit["remark"] ?? "") . ")";
                    $creditAmount = (float) ($credit["amount"] ?? 0);
                    $this->insertSupplierLedgerEntry($recordTime, $supplierId, $userId, 6, $remark, 3, $creditAmount);

                    $updateCredit = $this->db->prepare(
                        "UPDATE account_cashcredit
                         SET status = 2, paiddate = NOW(), paieduser = :user
                         WHERE logid = :logid",
                    );
                    $updateCredit->execute([
                        "user" => $userId,
                        "logid" => (int) ($credit["logid"] ?? 0),
                    ]);
                }
            }

            $remaining = round($dueAmount - $appliedAmount, 2);
            if ($remaining < 0) {
                throw new RuntimeException("Payment amount exceeds the remaining due amount.");
            }

            $update = $this->db->prepare(
                "UPDATE shop_grn_pay
                 SET cash_pay_amount = :cash_pay_amount,
                     chq_pay_amount = :chq_pay_amount,
                     due_amount = :due_amount,
                     payment_status = :payment_status,
                     complete_time = :complete_time
                 WHERE record_id = :record_id",
            );
            $update->execute([
                "cash_pay_amount" => $cashTotal,
                "chq_pay_amount" => $chequeTotal,
                "due_amount" => $remaining,
                "payment_status" => $remaining == 0.0 ? 0 : 1,
                "complete_time" => $remaining == 0.0 ? $recordTime : null,
                "record_id" => $recordId,
            ]);

            $log = $this->db->prepare(
                "INSERT INTO shop_grn_pay_log
                 (record_time, grn_code, grn_final_amount, pay_type, cash_pay_amount, chq_pay_amount, paid_user)
                 VALUES
                 (:record_time, :grn_code, :grn_final_amount, :pay_type, :cash_pay_amount, :chq_pay_amount, :paid_user)",
            );
            $log->execute([
                "record_time" => $recordTime,
                "grn_code" => $grnCode,
                "grn_final_amount" => $grnFinalAmount,
                "pay_type" => $payLogType,
                "cash_pay_amount" => $method === "cheque" ? 0 : $appliedAmount,
                "chq_pay_amount" => $method === "cheque" ? $appliedAmount : 0,
                "paid_user" => $userId,
            ]);

            if ($method === "credit") {
                $this->refreshSupplierCashCreditBalances();
            }

            $this->db->commit();

            return [
                "grn_refno" => $grnCode,
                "supplier_name" => (string) ($payment["supplier_name"] ?? ""),
                "method" => $method,
                "amount" => $appliedAmount,
                "due_amount" => $remaining,
                "recordtime" => $recordTime,
            ];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    private function insertSupplierLedgerEntry(string $recordTime, int $supplierId, int $userId, int $opType, string $details, int $payType, float $debit): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO account_supplier
             (recordtime, supplier, operator, op_type, details, paytype, debit, credit)
             VALUES
             (:recordtime, :supplier, :operator, :op_type, :details, :paytype, :debit, 0)",
        );
        $stmt->execute([
            "recordtime" => $recordTime,
            "supplier" => $supplierId,
            "operator" => $userId,
            "op_type" => $opType,
            "details" => $details,
            "paytype" => $payType,
            "debit" => $debit,
        ]);
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
