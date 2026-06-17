<?php

namespace Core\Console;

abstract class Command
{
    abstract public function signature();
    abstract public function handle($arguments);

    protected function info($message)
    {
        echo "\033[32m{$message}\033[0m\n";
    }

    protected function error($message)
    {
        echo "\033[31m{$message}\033[0m\n";
    }
}
