<?php

declare(strict_types=1);

namespace Commands;

use Core\Console\Command;

/**
 * SeedCommand
 *
 * Usage:
 *   php artisan db:seed                         — runs DatabaseSeeder
 *   php artisan db:seed --class=UserSeeder      — runs a specific seeder
 */
class SeedCommand extends Command
{
    private const SEEDER_PATH = BASE_PATH . '/database/seeders';

    public function signature()
    {
        return 'db:seed';
    }

    public function handle($arguments)
    {
        $class = $this->resolveClass($arguments);

        $this->loadSeeders();

        if (!class_exists($class)) {
            $this->error("Seeder class [{$class}] not found in database/seeders/.");
            return;
        }

        $db     = \Database::connect();
        $seeder = new $class($db);

        echo "\033[36mRunning seeder: {$class}\033[0m\n";

        $seeder->run();

        echo "\033[32mDatabase seeding completed.\033[0m\n";
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function resolveClass(array $arguments): string
    {
        foreach ($arguments as $arg) {
            if (str_starts_with($arg, '--class=')) {
                return trim(substr($arg, 8));
            }
        }

        return 'DatabaseSeeder';
    }

    private function loadSeeders(): void
    {
        // Load Seeder base
        require_once BASE_PATH . '/core/Seeders/Seeder.php';

        // Auto-load all seeders
        foreach (glob(self::SEEDER_PATH . '/*.php') ?: [] as $file) {
            require_once $file;
        }
    }
}
