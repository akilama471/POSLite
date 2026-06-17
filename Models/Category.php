<?php

declare(strict_types=1);

class Category extends Model
{
    protected $table = "prod_category";

    private function mapLegacyFields(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        if (isset($row['id']) && !isset($row['catid'])) {
            $row['catid'] = $row['id'];
        }

        return $row;
    }

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM prod_category ORDER BY id ASC",
        );

        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $rows);
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM prod_category WHERE catname = :name",
        );
        $stmt->execute(["name" => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM prod_category WHERE catname = :name LIMIT 1",
        );
        $stmt->execute(["name" => $name]);

        $category = $stmt->fetch();
        return $this->mapLegacyFields($category ?: null);
    }

    public function createCategory(string $name): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO prod_category (catname, eff_date) VALUES (:name, NOW())",
        );

        return $stmt->execute(["name" => $name]);
    }

    public function updateCategory(int $id, string $name): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE prod_category SET catname = :name WHERE id = :id",
        );

        return $stmt->execute([
            "name" => $name,
            "id" => $id,
        ]);
    }

    public function deleteCategory(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM prod_category WHERE id = :id",
        );

        return $stmt->execute(["id" => $id]);
    }
}
