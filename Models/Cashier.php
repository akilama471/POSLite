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
}
