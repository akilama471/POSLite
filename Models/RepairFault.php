<?php

declare(strict_types=1);

class RepairFault extends Model
{
    public function all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM repair_falut_list WHERE status < 2 ORDER BY recordid ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(string $name): void
    {
        $stmt = $this->db->prepare("INSERT INTO repair_falut_list (fault_name, status) VALUES (:name, 1)");
        $stmt->execute(["name" => $name]);
    }

    public function update(int $id, string $name): void
    {
        $stmt = $this->db->prepare("UPDATE repair_falut_list SET fault_name = :name WHERE recordid = :id");
        $stmt->execute(["name" => $name, "id" => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE repair_falut_list SET status = 2 WHERE recordid = :id");
        $stmt->execute(["id" => $id]);
    }
}
