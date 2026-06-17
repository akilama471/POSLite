<?php

declare(strict_types=1);

class Cashier extends Model
{
    protected $table = "cashier_point_control";

    public function slotForUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM cashier_point_control
             WHERE current_operator = :user_id
             LIMIT 1",
        );
        $stmt->execute(["user_id" => $userId]);

        $slot = $stmt->fetch();
        return $slot ?: null;
    }

    public function activeLog(int $slotId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM cashier_point_log
             WHERE operation_slot = :slot_id
               AND user_id = :user_id
               AND status = 1
             ORDER BY recordid DESC
             LIMIT 1",
        );
        $stmt->execute([
            "slot_id" => $slotId,
            "user_id" => $userId,
        ]);

        $log = $stmt->fetch();
        return $log ?: null;
    }

    public function latestLog(int $slotId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM cashier_point_log
             WHERE operation_slot = :slot_id
               AND user_id = :user_id
             ORDER BY recordid DESC
             LIMIT 1",
        );
        $stmt->execute([
            "slot_id" => $slotId,
            "user_id" => $userId,
        ]);

        $log = $stmt->fetch();
        return $log ?: null;
    }

    public function isActiveForUser(int $userId): bool
    {
        $slot = $this->slotForUser($userId);

        if ($slot === null || (int) ($slot["status"] ?? 0) !== 1) {
            return false;
        }

        return $this->activeLog((int) $slot["recordid"], $userId) !== null;
    }

    public function dutyContext(int $userId): array
    {
        $slot = $this->slotForUser($userId);
        $activeLog = null;
        $latestLog = null;

        if ($slot !== null) {
            $slotId = (int) $slot["recordid"];
            $activeLog = $this->activeLog($slotId, $userId);
            $latestLog = $this->latestLog($slotId, $userId);
        }

        return [
            "slot" => $slot,
            "activeLog" => $activeLog,
            "latestLog" => $latestLog,
            "isActive" => $slot !== null
                && (int) ($slot["status"] ?? 0) === 1
                && $activeLog !== null,
            "canStart" => $slot !== null && (int) ($slot["status"] ?? 0) === 0,
        ];
    }

    public function startDuty(int $slotId, int $shopId, int $userId, float $cashOpen, float $cardOpen): void
    {
        $this->db->beginTransaction();

        try {
            $recordTime = date("Y-m-d H:i:s");
            $legacyStamp = date("YmdHis");

            $insert = $this->db->prepare(
                "INSERT INTO cashier_point_log
                 (operation_slot, shop_id, user_id, cash_openbal, card_openbal, status, recordtime)
                 VALUES
                 (:slot_id, :shop_id, :user_id, :cash_openbal, :card_openbal, 1, :recordtime)",
            );
            $insert->execute([
                "slot_id" => $slotId,
                "shop_id" => $shopId,
                "user_id" => $userId,
                "cash_openbal" => $cashOpen,
                "card_openbal" => $cardOpen,
                "recordtime" => $recordTime,
            ]);

            $update = $this->db->prepare(
                "UPDATE cashier_point_control
                 SET current_operator = :user_id,
                     user_on = :user_on,
                     status = 1
                 WHERE recordid = :slot_id",
            );
            $update->execute([
                "user_id" => $userId,
                "user_on" => $legacyStamp,
                "slot_id" => $slotId,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function closeDuty(int $slotId, int $userId, float $cashClose, float $cardClose): void
    {
        $activeLog = $this->activeLog($slotId, $userId);

        if ($activeLog === null) {
            throw new RuntimeException("Cashier sign-in details not found.");
        }

        $this->db->beginTransaction();

        try {
            $now = date("Y-m-d H:i:s");

            $updateLog = $this->db->prepare(
                "UPDATE cashier_point_log
                 SET close_time = :close_time,
                     cash_closebal = :cash_closebal,
                     card_closebal = :card_closebal,
                     status = 0
                 WHERE recordid = :recordid",
            );
            $updateLog->execute([
                "close_time" => $now,
                "cash_closebal" => $cashClose,
                "card_closebal" => $cardClose,
                "recordid" => (int) $activeLog["recordid"],
            ]);

            $updateSlot = $this->db->prepare(
                "UPDATE cashier_point_control
                 SET user_off = :user_off,
                     status = 0,
                     current_operator = :user_id
                 WHERE recordid = :slot_id",
            );
            $updateSlot->execute([
                "user_off" => $now,
                "user_id" => $userId,
                "slot_id" => $slotId,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function addExpense(int $shopId, int $userId, int $accountId, float $amount, string $reason): void
    {
        $this->db->beginTransaction();

        try {
            $recordTime = date("Y-m-d H:i:s");
            $remark = "Expences Update:" . $reason;

            $insertCashBook = $this->db->prepare(
                "INSERT INTO cash_book
                 (op_date, shop, user, pay_type, remark, open_balance, cash_in, cash_out, close_balance)
                 VALUES
                 (:op_date, :shop, :user, 1, :remark, 0, 0, :cash_out, 0)"
            );
            $insertCashBook->execute([
                "op_date" => $recordTime,
                "shop" => $shopId,
                "user" => $userId,
                "remark" => $remark,
                "cash_out" => $amount,
            ]);

            $insertExpenseLog = $this->db->prepare(
                "INSERT INTO expence_log
                 (record_time, shop_id, operator_id, account_id, amount, remark)
                 VALUES
                 (:record_time, :shop_id, :operator_id, :account_id, :amount, :remark)"
            );
            $insertExpenseLog->execute([
                "record_time" => $recordTime,
                "shop_id" => $shopId,
                "operator_id" => $userId,
                "account_id" => $accountId,
                "amount" => $amount,
                "remark" => $reason,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function addCashIn(int $shopId, int $userId, int $accountId, float $amount, string $reason): void
    {
        $this->db->beginTransaction();

        try {
            $recordTime = date("Y-m-d H:i:s");
            $remark = "Cash In Update:" . $reason;

            $insertCashBook = $this->db->prepare(
                "INSERT INTO cash_book
                 (op_date, shop, user, pay_type, remark, open_balance, cash_in, cash_out, close_balance)
                 VALUES
                 (:op_date, :shop, :user, 1, :remark, 0, :cash_in, 0, 0)"
            );
            $insertCashBook->execute([
                "op_date" => $recordTime,
                "shop" => $shopId,
                "user" => $userId,
                "remark" => $remark,
                "cash_in" => $amount,
            ]);

            $insertCashInLog = $this->db->prepare(
                "INSERT INTO cashin_log
                 (record_time, shop_id, operator_id, account_id, amount, remark)
                 VALUES
                 (:record_time, :shop_id, :operator_id, :account_id, :amount, :remark)"
            );
            $insertCashInLog->execute([
                "record_time" => $recordTime,
                "shop_id" => $shopId,
                "operator_id" => $userId,
                "account_id" => $accountId,
                "amount" => $amount,
                "remark" => $reason,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    /**
     * Build a session balance summary for the close-duty confirmation screen.
     * Mirrors the logic in legacy shop_close.php.
     */
    public function getSessionSummary(int $slotId, int $userId, int $shopId): array
    {
        // Open balances from the active log
        $stmtLog = $this->db->prepare(
            "SELECT cash_openbal, card_openbal, recordtime
             FROM cashier_point_log
             WHERE operation_slot = :slot AND user_id = :user AND status = 1
             ORDER BY recordid DESC LIMIT 1"
        );
        $stmtLog->execute(['slot' => $slotId, 'user' => $userId]);
        $log      = $stmtLog->fetch();
        $openTime = $log['recordtime'] ?? date('Y-m-d H:i:s');
        $cashOpen = (float)($log['cash_openbal'] ?? 0);
        $cardOpen = (float)($log['card_openbal'] ?? 0);

        // Sum income / expenses by pay type
        $sumIn = function (int $pt) use ($openTime, $userId): float {
            $s = $this->db->prepare(
                "SELECT COALESCE(SUM(cash_in),0) FROM cash_book
                 WHERE op_date >= :since AND user = :user AND pay_type = :pt AND cash_in > 0"
            );
            $s->execute(['since' => $openTime, 'user' => $userId, 'pt' => $pt]);
            return (float)$s->fetchColumn();
        };

        $sumOut = function (int $pt) use ($openTime, $userId, $shopId): float {
            $s = $this->db->prepare(
                "SELECT COALESCE(SUM(cash_out),0) FROM cash_book
                 WHERE op_date >= :since AND user = :user AND shop = :shop AND pay_type = :pt AND cash_out > 0"
            );
            $s->execute(['since' => $openTime, 'user' => $userId, 'shop' => $shopId, 'pt' => $pt]);
            return (float)$s->fetchColumn();
        };

        $incCash  = $sumIn(1);
        $incCard  = $sumIn(2);
        $incCheq  = $sumIn(3);
        $expCash  = $sumOut(1);
        $expCard  = $sumOut(2);
        $expTotal = $expCash + $expCard + $sumOut(3);

        // Full transaction list for the breakdown table
        $stmtTx = $this->db->prepare(
            "SELECT pay_type, remark, cash_in, cash_out, op_date
             FROM cash_book
             WHERE op_date >= :since AND user = :user AND shop = :shop
             ORDER BY op_date ASC"
        );
        $stmtTx->execute(['since' => $openTime, 'user' => $userId, 'shop' => $shopId]);

        return [
            'cash_open_bal'  => $cashOpen,
            'card_open_bal'  => $cardOpen,
            'inc_cash'       => $incCash,
            'inc_card'       => $incCard,
            'inc_cheq'       => $incCheq,
            'exp_total'      => $expTotal,
            'sys_close_cash' => $cashOpen + $incCash - $expCash,
            'sys_close_card' => $cardOpen + $incCard - $expCard,
            'transactions'   => $stmtTx->fetchAll(),
        ];
    }

    /**
     * List active users for the same shop — for the transfer-to dropdown.
     */
    public function listShopUsers(int $shopId): array
    {
        $stmt = $this->db->prepare(
            "SELECT myid, visibledata FROM sys_user
             WHERE statusu = 1 AND (shop_id = :shop OR shop_id = 0)
             ORDER BY visibledata ASC"
        );
        $stmt->execute(['shop' => $shopId]);
        return $stmt->fetchAll();
    }

    /**
     * Close duty with optional slot transfer to another operator.
     * Pass transferToUserId = 0 to keep the slot with the current user.
     */
    public function closeDutyWithTransfer(
        int $slotId,
        int $userId,
        float $cashClose,
        float $cardClose,
        int $transferToUserId = 0
    ): void {
        $activeLog = $this->activeLog($slotId, $userId);
        if ($activeLog === null) {
            throw new RuntimeException("Cashier sign-in details not found.");
        }

        $this->db->beginTransaction();
        try {
            $now = date("Y-m-d H:i:s");

            $this->db->prepare(
                "UPDATE cashier_point_log
                 SET close_time = :ct, cash_closebal = :cc, card_closebal = :cd, status = 0
                 WHERE recordid = :id"
            )->execute([
                'ct' => $now, 'cc' => $cashClose,
                'cd' => $cardClose, 'id' => (int)$activeLog['recordid'],
            ]);

            $newOperator = $transferToUserId > 0 ? $transferToUserId : $userId;
            $this->db->prepare(
                "UPDATE cashier_point_control
                 SET user_off = :off, status = 0, current_operator = :op
                 WHERE recordid = :slot"
            )->execute(['off' => $now, 'op' => $newOperator, 'slot' => $slotId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
