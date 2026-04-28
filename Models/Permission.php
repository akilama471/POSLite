<?php

declare(strict_types=1);

class Permission extends Model
{
    protected $table = "sys_privilegemap";

    public function forUser(int $userId): array
    {
        return $this->forMap($userId);
    }

    public function forMap(int $mapId): array
    {
        $stmt = $this->db->prepare(
            "SELECT linkdata, linkvalue FROM sys_privilegemap WHERE mapid = :mapid",
        );
        $stmt->execute(["mapid" => $mapId]);

        $permissions = [];

        foreach ($stmt->fetchAll() as $row) {
            $permissions[$row["linkdata"]] = (int) $row["linkvalue"] === 1;
        }

        return $permissions;
    }

    public function syncMap(int $mapId, array $values): void
    {
        $select = $this->db->prepare(
            "SELECT COUNT(*) FROM sys_privilegemap WHERE mapid = :mapid AND linkdata = :linkdata",
        );
        $update = $this->db->prepare(
            "UPDATE sys_privilegemap SET linkvalue = :linkvalue WHERE mapid = :mapid AND linkdata = :linkdata",
        );
        $insert = $this->db->prepare(
            "INSERT INTO sys_privilegemap (mapid, linkdata, linkvalue) VALUES (:mapid, :linkdata, :linkvalue)",
        );

        foreach ($values as $linkData => $linkValue) {
            $select->execute([
                "mapid" => $mapId,
                "linkdata" => $linkData,
            ]);

            $payload = [
                "mapid" => $mapId,
                "linkdata" => $linkData,
                "linkvalue" => $linkValue ? 1 : 0,
            ];

            if ((int) $select->fetchColumn() > 0) {
                $update->execute($payload);
            } else {
                $insert->execute($payload);
            }
        }
    }
}
