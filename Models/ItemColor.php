<?php

declare(strict_types=1);

class ItemColor extends Model
{
    protected $table = "prod_item_color";

    private function mapLegacyFields(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        if (isset($row['id']) && !isset($row['color_id'])) {
            $row['color_id'] = $row['id'];
        }

        return $row;
    }

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM prod_item_color ORDER BY id ASC",
        );

        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $rows);
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM prod_item_color WHERE color_name = :name";
        $params = ["name" => $name];

        if ($excludeId !== null) {
            $sql .= " AND id <> :color_id";
            $params["color_id"] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createColor(string $name): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO prod_item_color (color_name, eff_date)
             VALUES (:color_name, :eff_date)",
        );

        return $stmt->execute([
            "color_name" => $name,
            "eff_date" => date("Y-m-d H:i:s"),
        ]);
    }

    public function updateColor(int $colorId, string $name): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE prod_item_color
             SET color_name = :color_name
             WHERE id = :color_id",
        );

        return $stmt->execute([
            "color_name" => $name,
            "color_id" => $colorId,
        ]);
    }

    public function deleteColor(int $colorId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM prod_item_color WHERE id = :color_id",
        );

        return $stmt->execute(["color_id" => $colorId]);
    }
}
