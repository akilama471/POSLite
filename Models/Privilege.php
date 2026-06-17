<?php

declare(strict_types=1);

class Privilege extends Model
{
    protected $table = "sys_privilege";

    private function mapLegacyFields(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        if (isset($row['id']) && !isset($row['privilegeid'])) {
            $row['privilegeid'] = $row['id'];
        }

        return $row;
    }

    public function findByPrivilegeId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sys_privilege WHERE id = :id LIMIT 1",
        );
        $stmt->execute(["id" => $id]);

        $row = $stmt->fetch();
        return $this->mapLegacyFields($row ?: null);
    }

    public function allOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM sys_privilege ORDER BY id ASC",
        );

        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $rows);
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
            "UPDATE sys_privilege SET privilegename = :name WHERE id = :id",
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
