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
}
