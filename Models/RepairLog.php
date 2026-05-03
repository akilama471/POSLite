<?php

declare(strict_types=1);

class RepairLog extends Model
{
    public function getLogs(string $jobNumber): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM rapair_job_log
             WHERE job_number = :job
             ORDER BY record_time DESC"
        );
        $stmt->execute(["job" => $jobNumber]);
        return $stmt->fetchAll();
    }

    public function addLog(string $jobNumber, string $opType, string $itemName, string $itemRef, float $costPrice, float $sellPrice, int $userId): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO rapair_job_log
             (job_number, op_type, a_item_name, a_item_gen_refno, item_cost_price, item_sell_price, user_id, record_time)
             VALUES
             (:job, :op_type, :item_name, :item_ref, :cost, :sell, :user, :now)"
        );
        $stmt->execute([
            "job" => $jobNumber,
            "op_type" => $opType,
            "item_name" => $itemName,
            "item_ref" => $itemRef,
            "cost" => $costPrice,
            "sell" => $sellPrice,
            "user" => $userId,
            "now" => date('Y-m-d H:i:s'),
        ]);
    }
}
