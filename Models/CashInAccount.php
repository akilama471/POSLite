<?php

declare(strict_types=1);

class CashInAccount extends Model
{
    public function all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM cashin_account ORDER BY recordid ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM cashin_account WHERE recordid = :id");
        $stmt->execute(["id" => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM cashin_account WHERE acc_name = :name LIMIT 1");
        $stmt->execute(["name" => $name]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $name): int
    {
        $stmt = $this->db->prepare("INSERT INTO cashin_account (acc_name, eff_date) VALUES (:name, :eff_date)");
        $stmt->execute([
            "name" => $name,
            "eff_date" => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name): void
    {
        $stmt = $this->db->prepare("UPDATE cashin_account SET acc_name = :name WHERE recordid = :id");
        $stmt->execute([
            "name" => $name,
            "id" => $id,
        ]);
    }
}
