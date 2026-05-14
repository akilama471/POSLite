<?php

declare(strict_types=1);

namespace Core\Console;

use Commands\ListCommand;
use Commands\MakeMigrationCommand;
use Commands\MigrateCommand;

class Kernel
{
    private array $commands = [];

    public function __construct()
    {
        $this->register(new ListCommand());
        $this->register(new MigrateCommand());
        $this->register(new MakeMigrationCommand());
    }

    private function register($command): void
    {
        $this->commands[$command->signature()] = $command;
    }

    public function handle($argv): void
    {
        $name = $argv[1] ?? 'list';

        if (!isset($this->commands[$name])) {
            echo "Command not found\n";
            return;
        }

        $this->commands[$name]->handle(array_slice($argv, 2));
    }
}
