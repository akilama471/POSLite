<?php

declare(strict_types=1);

namespace Commands;

use Core\Console\Command;

class MakeMigrationCommand extends Command
{
    public function signature()
    {
        return 'make:migration';
    }

    public function handle($arguments)
    {
        $name = $arguments[0] ?? null;
        if (!$name) {
            $this->error('Usage: php artisan make:migration <name>');
            return;
        }

        $timestamp = date('Y_m_d_His');
        $file = dirname(__DIR__) . '/database/migrations/' . $timestamp . '_' . $name . '.php';

        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        $template = <<<'PHP'
<?php

declare(strict_types=1);

use Core\Migrations\Migration;
use PDO;

return new class extends Migration {
    public function up(PDO $db): void
    {
        // Write SQL here.
    }

    public function down(PDO $db): void
    {
        // Optional rollback.
    }
};
PHP;

        file_put_contents($file, $template);
        $this->info("Migration created: " . basename($file));
    }
}
