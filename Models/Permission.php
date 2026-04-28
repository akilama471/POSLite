<?php

declare(strict_types=1);

class Permission extends Model
{
    protected $table = "sys_privilegemap";

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT linkdata, linkvalue FROM sys_privilegemap WHERE mapid = :mapid",
        );
        $stmt->execute(["mapid" => $userId]);

        $permissions = [];

        foreach ($stmt->fetchAll() as $row) {
            $permissions[$row["linkdata"]] = (int) $row["linkvalue"] === 1;
        }

        return $permissions;
    }
}
