<?php

declare(strict_types=1);

namespace Core\Seeders;

use PDO;

abstract class Seeder
{
    public function __construct(protected PDO $db) {}

    abstract public function run(): void;

    // ── Helpers ────────────────────────────────────────────────────────────────

    protected function info(string $message): void
    {
        echo "\033[32m  " . $message . "\033[0m\n";
    }

    protected function warn(string $message): void
    {
        echo "\033[33m  " . $message . "\033[0m\n";
    }

    /**
     * Check if a row already exists to keep seeders idempotent.
     *
     * Example: $this->exists('sys_user', 'ankaya', 'admin')
     */
    protected function exists(string $table, string $column, mixed $value): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :value LIMIT 1"
        );
        $stmt->execute(['value' => $value]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Call another seeder from within a seeder.
     */
    protected function call(string $seederClass): void
    {
        $seeder = new $seederClass($this->db);
        $seeder->run();
    }
}
