<?php

declare(strict_types=1);

use Core\Seeders\Seeder;

/**
 * UserSeeder
 *
 * Seeds default system users into `sys_user`.
 *
 * Table schema (from migration 2026_05_14_000006):
 *   id          BIGINT PK AUTO_INCREMENT
 *   username    VARCHAR(100) UNIQUE NOT NULL
 *   password    VARCHAR(255) NOT NULL  ← bcrypt hash
 *   full_name   VARCHAR(150)
 *   user_role   VARCHAR(100)
 *   status      TINYINT(1) DEFAULT 1   (1 = active, 0 = inactive)
 *   created_at  TIMESTAMP
 *   updated_at  TIMESTAMP
 *
 * Run:  php artisan db:seed
 *       php artisan db:seed --class=UserSeeder
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->info("Seeding users…");

        foreach ($this->users() as $user) {
            if ($this->exists('sys_user', 'username', $user['username'])) {
                $this->warn("Skipped — already exists: {$user['username']}");
                continue;
            }

            $stmt = $this->db->prepare("
                INSERT INTO sys_user (username, password, full_name, user_role, status)
                VALUES (:username, :password, :full_name, :user_role, 1)
            ");

            $stmt->execute([
                'username'  => $user['username'],
                'password'  => password_hash($user['plain_password'], PASSWORD_BCRYPT),
                'full_name' => $user['full_name'],
                'user_role' => $user['user_role'],
            ]);

            $this->info("Created  — {$user['full_name']} ({$user['username']})  password: {$user['plain_password']}");
        }

        $this->info("Done.");
    }

    // ── User definitions ───────────────────────────────────────────────────────

    private function users(): array
    {
        return [
            [
                'username'       => 'admin',
                'plain_password' => 'admin@123',
                'full_name'      => 'System Administrator',
                'user_role'      => 'admin',
            ],
            [
                'username'       => 'manager',
                'plain_password' => 'manager@123',
                'full_name'      => 'Store Manager',
                'user_role'      => 'manager',
            ],
            [
                'username'       => 'cashier',
                'plain_password' => 'cashier@123',
                'full_name'      => 'Default Cashier',
                'user_role'      => 'cashier',
            ],
        ];
    }
}
