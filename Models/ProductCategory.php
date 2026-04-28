<?php

declare(strict_types=1);

class ProductCategory extends Model
{
    protected $table = "prod_category";

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM prod_category ORDER BY catid ASC",
        );

        return $stmt->fetchAll();
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM prod_category WHERE catname = :name",
        );
        $stmt->execute(["name" => $name]);

        return (int) $stmt->fetchColumn() > 0;
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
            "UPDATE prod_category SET catname = :name WHERE catid = :id",
        );

        return $stmt->execute([
            "name" => $name,
            "id" => $id,
        ]);
    }

    public function deleteCategory(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM prod_category WHERE catid = :id",
        );

        return $stmt->execute(["id" => $id]);
    }
}
