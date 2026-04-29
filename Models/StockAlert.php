<?php

declare(strict_types=1);

class StockAlert extends Model
{
    protected $table = "stock_alert";

    public function activeByShop(int $shopId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM stock_alert
             WHERE shop_id = :shop_id
               AND exp_time >= :now_time
             ORDER BY recordid ASC",
        );
        $stmt->execute([
            "shop_id" => $shopId,
            "now_time" => date("Y-m-d H:i:s"),
        ]);

        return $stmt->fetchAll();
    }

    public function findActiveById(int $recordId, int $shopId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM stock_alert
             WHERE recordid = :recordid
               AND shop_id = :shop_id
               AND exp_time >= :now_time
             LIMIT 1",
        );
        $stmt->execute([
            "recordid" => $recordId,
            "shop_id" => $shopId,
            "now_time" => date("Y-m-d H:i:s"),
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existsActiveForItem(int $shopId, int $itemId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM stock_alert
             WHERE shop_id = :shop_id
               AND item_id = :item_id
               AND exp_time >= :now_time",
        );
        $stmt->execute([
            "shop_id" => $shopId,
            "item_id" => $itemId,
            "now_time" => date("Y-m-d H:i:s"),
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createAlert(int $shopId, array $item, int $alertQty): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO stock_alert
             (shop_id, item_name, item_id, cat_id, type, alert_qty, current_qty, exp_time, eff_time)
             VALUES
             (:shop_id, :item_name, :item_id, :cat_id, :type, :alert_qty, -1, :exp_time, :eff_time)",
        );

        return $stmt->execute([
            "shop_id" => $shopId,
            "item_name" => $item["item_name"],
            "item_id" => (int) $item["item_id"],
            "cat_id" => (int) $item["item_cat"],
            "type" => (int) $item["used_type"],
            "alert_qty" => $alertQty,
            "exp_time" => "2050-01-01 00:00:00",
            "eff_time" => date("Y-m-d H:i:s"),
        ]);
    }

    public function updateAlertQty(int $recordId, int $shopId, int $alertQty): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE stock_alert
             SET alert_qty = :alert_qty
             WHERE recordid = :recordid
               AND shop_id = :shop_id",
        );

        return $stmt->execute([
            "alert_qty" => $alertQty,
            "recordid" => $recordId,
            "shop_id" => $shopId,
        ]);
    }

    public function expireAlert(int $recordId, int $shopId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE stock_alert
             SET exp_time = :exp_time
             WHERE recordid = :recordid
               AND shop_id = :shop_id",
        );

        return $stmt->execute([
            "exp_time" => date("Y-m-d H:i:s"),
            "recordid" => $recordId,
            "shop_id" => $shopId,
        ]);
    }
}
