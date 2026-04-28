<?php

declare(strict_types=1);

class Privilege extends Model
{
    protected $table = "sys_privilege";

    public function findByPrivilegeId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sys_privilege WHERE privilegeid = :id LIMIT 1",
        );
        $stmt->execute(["id" => $id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM sys_privilege ORDER BY privilegeid ASC",
        );

        return $stmt->fetchAll();
    }

    public function createByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sys_privilege (privilegename) VALUES (:name)",
        );

        return $stmt->execute(["name" => $name]);
    }

    public function updateName(int $id, string $name): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_privilege SET privilegename = :name WHERE privilegeid = :id",
        );

        return $stmt->execute([
            "name" => $name,
            "id" => $id,
        ]);
    }

    public function existsByName(string $name): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM sys_privilege WHERE privilegename = :name",
        );
        $stmt->execute(["name" => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
