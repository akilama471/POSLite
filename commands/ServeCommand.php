<?php

declare(strict_types=1);

namespace Commands;

use Core\Console\Command;

class ServeCommand extends Command
{
    public function signature()
    {
        return 'serve';
    }

    public function handle($arguments)
    {
        $host = '127.0.0.1';
        $port = '8014';

        for ($i = 0; $i < count($arguments); $i++) {
            $argument = $arguments[$i];

            if (str_starts_with($argument, '--host=')) {
                $host = substr($argument, 7);
                continue;
            }

            if ($argument === '--host' && isset($arguments[$i + 1])) {
                $host = $arguments[++$i];
                continue;
            }

            if (str_starts_with($argument, '--port=')) {
                $port = substr($argument, 7);
                continue;
            }

            if ($argument === '--port' && isset($arguments[$i + 1])) {
                $port = $arguments[++$i];
            }
        }

        if ($host === '' || !preg_match('/^[a-zA-Z0-9.\-:]+$/', $host)) {
            $this->error('Invalid host.');
            return;
        }

        if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            $this->error('Invalid port. Use a number between 1 and 65535.');
            return;
        }

        $publicPath = dirname(__DIR__) . '/public';

        if (!is_dir($publicPath)) {
            $this->error('Public directory was not found.');
            return;
        }

        $address = "{$host}:{$port}";
        $this->info("Starting development server: http://{$address}");
        $this->info('Press Ctrl+C to stop the server.');

        passthru(escapeshellarg(PHP_BINARY) . ' -S ' . escapeshellarg($address) . ' -t ' . escapeshellarg($publicPath));
    }
}
