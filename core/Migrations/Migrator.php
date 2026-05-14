<?php

declare(strict_types=1);

namespace Core\Migrations;

use PDO;

class Migrator
{
    public function __construct(
        private PDO $db,
        private string $migrationPath
    ) {}

    public function migrate(): int
    {
        $this->ensureRepository();

        $ran = $this->ranMigrations();
        $files = glob($this->migrationPath . '/*.php') ?: [];
        sort($files);

        $batch = $this->nextBatchNumber();
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $ran, true)) {
                continue;
            }

            $migration = require $file;
            if (!$migration instanceof Migration) {
                throw new \RuntimeException("Invalid migration: {$name}");
            }

            try {
                if (!$this->db->inTransaction()) {
                    $this->db->beginTransaction();
                }

                $migration->up($this->db);

                $stmt = $this->db->prepare('INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)');
                $stmt->execute(['migration' => $name, 'batch' => $batch]);

                if ($this->db->inTransaction()) {
                    $this->db->commit();
                }
            } catch (\Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $e;
            }

            $count++;
        }

        return $count;
    }

    private function ensureRepository(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS migrations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL,
            migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    private function ranMigrations(): array
    {
        $stmt = $this->db->query('SELECT migration FROM migrations');
        return array_map(static fn(array $row): string => (string) $row['migration'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function nextBatchNumber(): int
    {
        $stmt = $this->db->query('SELECT COALESCE(MAX(batch), 0) + 1 AS next_batch FROM migrations');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($result['next_batch'] ?? 1);
    }
}
