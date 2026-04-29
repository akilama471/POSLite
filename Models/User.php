<?php

declare(strict_types=1);

class User extends Model
{
    protected $table = "sys_user";

    public function findActiveByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sys_user WHERE ankaya = :username AND statusu = 1 LIMIT 1",
        );
        $stmt->execute(["username" => $username]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function listManageableUsers(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM sys_user WHERE myid > 1 AND statusu < 4 ORDER BY myid ASC",
        );

        return $stmt->fetchAll();
    }

    public function existsByUsername(string $username): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM sys_user WHERE ankaya = :username",
        );
        $stmt->execute(["username" => $username]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createLegacyUser(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sys_user
            (ankaya, murapadaya, statusu, company, privilageid, visibledata, shop_id, addeddate)
            VALUES
            (:username, :password, 1, 1, :privilege_id, :display_name, :shop_id, :addeddate)",
        );

        $stmt->execute([
            "username" => $data["username"],
            "password" => $data["password"],
            "privilege_id" => $data["privilege_id"],
            "display_name" => $data["display_name"],
            "shop_id" => $data["shop_id"],
            "addeddate" => $data["addeddate"],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sys_user WHERE myid = :id LIMIT 1");
        $stmt->execute(["id" => $id]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findActiveById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sys_user WHERE myid = :id AND statusu = 1 LIMIT 1",
        );
        $stmt->execute(["id" => $id]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateStatus(int $id, int $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_user SET statusu = :status WHERE myid = :id",
        );

        return $stmt->execute([
            "status" => $status,
            "id" => $id,
        ]);
    }

    public function resetPassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_user SET murapadaya = :password WHERE myid = :id",
        );

        return $stmt->execute([
            "password" => $passwordHash,
            "id" => $id,
        ]);
    }

    public function updatePrivilege(int $id, int $privilegeId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_user SET privilageid = :privilege_id WHERE myid = :id",
        );

        return $stmt->execute([
            "privilege_id" => $privilegeId,
            "id" => $id,
        ]);
    }

    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_user
             SET visibledata = :display_name, email = :email, mobile = :mobile
             WHERE myid = :id",
        );

        return $stmt->execute([
            "display_name" => $data["display_name"],
            "email" => $data["email"],
            "mobile" => $data["mobile"],
            "id" => $id,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_user SET murapadaya = :password WHERE myid = :id",
        );

        return $stmt->execute([
            "password" => $passwordHash,
            "id" => $id,
        ]);
    }
}
