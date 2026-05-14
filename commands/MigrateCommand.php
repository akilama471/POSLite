<?php

declare(strict_types=1);

namespace Commands;

use Core\Console\Command;
use Core\Migrations\Migrator;

class MigrateCommand extends Command
{
    public function signature()
    {
        return 'migrate';
    }

    public function handle($arguments)
    {
        $db = \Database::connect();
        $migrator = new Migrator($db, dirname(__DIR__) . '/database/migrations');

        $count = $migrator->migrate();

        if ($count === 0) {
            $this->info('Nothing to migrate.');
            return;
        }

        $this->info("Migrated {$count} file(s) successfully.");
    }
}
