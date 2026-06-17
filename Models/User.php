<?php

declare(strict_types=1);

class User extends Model
{
    protected $table = "sys_user";

    /**
     * Map database columns (new schema) to legacy columns (old schema)
     * so that the rest of the application using old field names continues to work.
     */
    private function mapLegacyFields(?array $user): ?array
    {
        if ($user === null) {
            return null;
        }

        // PK: id -> myid
        if (isset($user['id']) && !isset($user['myid'])) {
            $user['myid'] = $user['id'];
        } elseif (isset($user['myid']) && !isset($user['id'])) {
            $user['id'] = $user['myid'];
        }

        // username -> ankaya
        if (isset($user['username']) && !isset($user['ankaya'])) {
            $user['ankaya'] = $user['username'];
        } elseif (isset($user['ankaya']) && !isset($user['username'])) {
            $user['username'] = $user['ankaya'];
        }

        // password -> murapadaya
        if (isset($user['password']) && !isset($user['murapadaya'])) {
            $user['murapadaya'] = $user['password'];
        } elseif (isset($user['murapadaya']) && !isset($user['password'])) {
            $user['password'] = $user['murapadaya'];
        }

        // full_name -> visibledata
        if (isset($user['full_name']) && !isset($user['visibledata'])) {
            $user['visibledata'] = $user['full_name'];
        } elseif (isset($user['visibledata']) && !isset($user['full_name'])) {
            $user['full_name'] = $user['visibledata'];
        }

        // status -> statusu
        if (isset($user['status']) && !isset($user['statusu'])) {
            $user['statusu'] = $user['status'];
        } elseif (isset($user['statusu']) && !isset($user['status'])) {
            $user['status'] = $user['statusu'];
        }

        // Map role string to privilege id
        if (isset($user['user_role'])) {
            $role = strtolower((string)$user['user_role']);
            if ($role === 'admin') {
                $user['privilageid'] = 1;
            } elseif ($role === 'manager') {
                $user['privilageid'] = 3;
            } else {
                $user['privilageid'] = 2; // cashier
            }
        } elseif (isset($user['privilageid'])) {
            $privId = (int)$user['privilageid'];
            if ($privId === 1) {
                $user['user_role'] = 'admin';
            } elseif ($privId === 3) {
                $user['user_role'] = 'manager';
            } else {
                $user['user_role'] = 'cashier';
            }
        } else {
            $user['privilageid'] = 2;
            $user['user_role'] = 'cashier';
        }

        // Default shop_id to 1 (or 0 for admin to show all shops)
        if (!isset($user['shop_id'])) {
            $user['shop_id'] = ($user['user_role'] === 'admin') ? 0 : 1;
        }

        return $user;
    }

    public function findActiveByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sys_user WHERE username = :username AND status = 1 LIMIT 1",
        );
        $stmt->execute(["username" => $username]);

        $user = $stmt->fetch();
        return $this->mapLegacyFields($user ?: null);
    }

    public function listManageableUsers(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM sys_user WHERE id > 1 AND status < 4 ORDER BY id ASC",
        );

        $users = $stmt->fetchAll();
        return array_map([$this, 'mapLegacyFields'], $users);
    }

    public function existsByUsername(string $username): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM sys_user WHERE username = :username",
        );
        $stmt->execute(["username" => $username]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function createLegacyUser(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sys_user
            (username, password, full_name, user_role, status)
            VALUES
            (:username, :password, :display_name, :user_role, 1)",
        );

        $role = 'cashier';
        if ((int)$data["privilege_id"] === 1) {
            $role = 'admin';
        } elseif ((int)$data["privilege_id"] === 3) {
            $role = 'manager';
        }

        $stmt->execute([
            "username" => $data["username"],
            "password" => $data["password"], // Stores hash (bcrypt or legacy SHA-1)
            "display_name" => $data["display_name"],
            "user_role" => $role,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM sys_user WHERE id = :id LIMIT 1");
        $stmt->execute(["id" => $id]);

        $user = $stmt->fetch();
        return $this->mapLegacyFields($user ?: null);
    }

    public function findActiveById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sys_user WHERE id = :id AND status = 1 LIMIT 1",
        );
        $stmt->execute(["id" => $id]);

        $user = $stmt->fetch();
        return $this->mapLegacyFields($user ?: null);
    }

    public function updateStatus(int $id, int $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_user SET status = :status WHERE id = :id",
        );

        return $stmt->execute([
            "status" => $status,
            "id" => $id,
        ]);
    }

    public function resetPassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_user SET password = :password WHERE id = :id",
        );

        return $stmt->execute([
            "password" => $passwordHash,
            "id" => $id,
        ]);
    }

    public function updatePrivilege(int $id, int $privilegeId): bool
    {
        $role = 'cashier';
        if ($privilegeId === 1) {
            $role = 'admin';
        } elseif ($privilegeId === 3) {
            $role = 'manager';
        }

        $stmt = $this->db->prepare(
            "UPDATE sys_user SET user_role = :role WHERE id = :id",
        );

        return $stmt->execute([
            "role" => $role,
            "id" => $id,
        ]);
    }

    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_user
             SET full_name = :display_name
             WHERE id = :id",
        );

        return $stmt->execute([
            "display_name" => $data["display_name"],
            "id" => $id,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sys_user SET password = :password WHERE id = :id",
        );

        return $stmt->execute([
            "password" => $passwordHash,
            "id" => $id,
        ]);
    }

    /**
     * Verify a password (bcrypt or legacy SHA-1 with auto-upgrade).
     */
    public function verifyPassword(array $user, string $password): bool
    {
        $hash = $user['password'] ?? $user['murapadaya'] ?? '';

        // 1. Try bcrypt
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$')) {
            return password_verify($password, $hash);
        }

        // 2. Fallback to legacy SHA-1
        $sha1 = sha1($password);
        if (hash_equals($sha1, $hash) || hash_equals(strtolower($sha1), strtolower($hash))) {
            // Upgrade hash to bcrypt
            $this->upgradePasswordHash((int)($user['id'] ?? $user['myid']), $password);
            return true;
        }

        return false;
    }

    private function upgradePasswordHash(int $userId, string $plainPassword): bool
    {
        $newHash = password_hash($plainPassword, PASSWORD_BCRYPT);
        return $this->updatePassword($userId, $newHash);
    }
}
