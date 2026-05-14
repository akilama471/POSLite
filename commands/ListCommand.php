<?php

declare(strict_types=1);

namespace Commands;

use Core\Console\Command;

class ListCommand extends Command
{
    public function signature()
    {
        return 'list';
    }

    public function handle($arguments)
    {
        $this->info('Available commands:');
        echo "list\n";
        echo "migrate\n";
        echo "make:migration\n";
    }
}
