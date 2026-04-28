<?php

declare(strict_types=1);

class Dashboard extends Model
{
    protected $table = "dash_data";

    public function summary(int $userId, int $shopId): array
    {
        if ($userId === 1) {
            $stmt = $this->db->query(
                "SELECT
                    COALESCE(SUM(today_sale), 0) AS today_sale,
                    COALESCE(SUM(today_purchaces), 0) AS today_purchases,
                    COALESCE(SUM(bill_count), 0) AS bill_count,
                    COALESCE(SUM(expence), 0) AS expense_total,
                    COALESCE(SUM(income), 0) AS income_total,
                    COALESCE(SUM(repair_count), 0) AS repair_count,
                    COALESCE(SUM(repair_done), 0) AS repair_done,
                    COALESCE(SUM(return_bill), 0) AS return_bill
                 FROM dash_data",
            );

            return $stmt->fetch() ?: [];
        }

        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(today_sale, 0) AS today_sale,
                COALESCE(today_purchaces, 0) AS today_purchases,
                COALESCE(bill_count, 0) AS bill_count,
                COALESCE(expence, 0) AS expense_total,
                COALESCE(income, 0) AS income_total,
                COALESCE(repair_count, 0) AS repair_count,
                COALESCE(repair_done, 0) AS repair_done,
                COALESCE(return_bill, 0) AS return_bill
             FROM dash_data
             WHERE shop_id = :shop_id
             LIMIT 1",
        );
        $stmt->execute(["shop_id" => $shopId]);

        return $stmt->fetch() ?: [];
    }

    public function salesTrend(int $userId, int $shopId): array
    {
        $columns = [];

        for ($i = 15; $i > 0; $i--) {
            $columns[] = "COALESCE(SUM(sale_trend_{$i}), 0) AS sale_trend_{$i}";
        }

        $sql = "SELECT " . implode(", ", $columns) . " FROM dash_data";
        $params = [];

        if ($userId !== 1) {
            $sql .= " WHERE shop_id = :shop_id";
            $params["shop_id"] = $shopId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        $trend = [];

        for ($i = 15; $i > 0; $i--) {
            $trend[] = [
                "label" => date("Y-m-d", strtotime("-" . ($i - 1) . " days")),
                "value" => (float) ($row["sale_trend_{$i}"] ?? 0),
            ];
        }

        return $trend;
    }

    public function topItems(int $userId, int $shopId): array
    {
        $rows = $this->fetchRows($userId, $shopId);
        return $this->collectSlots($rows, "move_part_", 5);
    }

    public function topPhones(int $userId, int $shopId): array
    {
        $rows = $this->fetchRows($userId, $shopId);
        return $this->collectSlots($rows, "move_part_2_", 5);
    }

    public function topCards(int $userId, int $shopId): array
    {
        $rows = $this->fetchRows($userId, $shopId);
        return $this->collectSlots($rows, "move_part_3_", 5);
    }

    private function fetchRows(int $userId, int $shopId): array
    {
        if ($userId === 1) {
            $stmt = $this->db->query("SELECT * FROM dash_data");
            return $stmt->fetchAll();
        }

        $stmt = $this->db->prepare("SELECT * FROM dash_data WHERE shop_id = :shop_id");
        $stmt->execute(["shop_id" => $shopId]);

        return $stmt->fetchAll();
    }

    private function collectSlots(array $rows, string $prefix, int $maxSlots): array
    {
        $items = [];

        foreach ($rows as $row) {
            for ($slot = 1; $slot <= $maxSlots; $slot++) {
                $label = trim((string) ($row["{$prefix}{$slot}"] ?? ""));
                $count = (int) ($row["{$prefix}{$slot}_count"] ?? 0);

                if ($label === "" || $count <= 0) {
                    continue;
                }

                $items[] = [
                    "label" => $label,
                    "count" => $count,
                ];
            }
        }

        usort($items, fn ($left, $right) => $right["count"] <=> $left["count"]);
        return array_slice($items, 0, 5);
    }
}
