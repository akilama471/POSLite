<?php

declare(strict_types=1);

class Dashboard extends Model
{
    protected $table = "dash_data";

    public function summary(int $userId, int $shopId): array
    {
        $prefix = ($userId === 1) ? "" : "shop_{$shopId}_";

        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN data_key = :today_sale THEN CAST(data_value AS DECIMAL(12,2)) END), 0) AS today_sale,
                COALESCE(SUM(CASE WHEN data_key = :today_purchases THEN CAST(data_value AS DECIMAL(12,2)) END), 0) AS today_purchases,
                COALESCE(SUM(CASE WHEN data_key = :bill_count THEN CAST(data_value AS DECIMAL(12,2)) END), 0) AS bill_count,
                COALESCE(SUM(CASE WHEN data_key = :expense_total THEN CAST(data_value AS DECIMAL(12,2)) END), 0) AS expense_total,
                COALESCE(SUM(CASE WHEN data_key = :income_total THEN CAST(data_value AS DECIMAL(12,2)) END), 0) AS income_total,
                COALESCE(SUM(CASE WHEN data_key = :repair_count THEN CAST(data_value AS DECIMAL(12,2)) END), 0) AS repair_count,
                COALESCE(SUM(CASE WHEN data_key = :repair_done THEN CAST(data_value AS DECIMAL(12,2)) END), 0) AS repair_done,
                COALESCE(SUM(CASE WHEN data_key = :return_bill THEN CAST(data_value AS DECIMAL(12,2)) END), 0) AS return_bill
             FROM dash_data"
        );

        $stmt->execute([
            "today_sale" => $prefix . "today_sale",
            "today_purchases" => $prefix . "today_purchaces",
            "bill_count" => $prefix . "bill_count",
            "expense_total" => $prefix . "expence",
            "income_total" => $prefix . "income",
            "repair_count" => $prefix . "repair_count",
            "repair_done" => $prefix . "repair_done",
            "return_bill" => $prefix . "return_bill",
        ]);

        return $stmt->fetch() ?: [];
    }

    public function salesTrend(int $userId, int $shopId): array
    {
        $prefix = ($userId === 1) ? "" : "shop_{$shopId}_";

        $cases = [];
        $params = [];
        for ($i = 15; $i > 0; $i--) {
            $key = $prefix . "sale_trend_{$i}";
            $cases[] = "COALESCE(SUM(CASE WHEN data_key = :key_{$i} THEN CAST(data_value AS DECIMAL(12,2)) END), 0) AS sale_trend_{$i}";
            $params["key_{$i}"] = $key;
        }

        $sql = "SELECT " . implode(", ", $cases) . " FROM dash_data";
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
        $stmt = $this->db->query("SELECT * FROM dash_data");
        $allRows = $stmt->fetchAll();

        $data = [];
        $prefix = ($userId === 1) ? "" : "shop_{$shopId}_";

        foreach ($allRows as $row) {
            $key = $row['data_key'];
            if ($userId !== 1) {
                if (str_starts_with($key, $prefix)) {
                    $cleanKey = substr($key, strlen($prefix));
                    $data[$cleanKey] = $row['data_value'];
                }
            } else {
                if (preg_match('/^shop_\d+_(.+)$/', $key, $matches)) {
                    $cleanKey = $matches[1];
                    if (str_ends_with($cleanKey, '_count')) {
                        $data[$cleanKey] = ($data[$cleanKey] ?? 0) + (int)$row['data_value'];
                    } else {
                        $data[$cleanKey] = $row['data_value'];
                    }
                } else {
                    $data[$key] = $row['data_value'];
                }
            }
        }

        return [$data];
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
