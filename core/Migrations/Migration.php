<?php

declare(strict_types=1);

namespace Core\Migrations;

use PDO;

abstract class Migration
{
    abstract public function up(PDO $db): void;

    public function down(PDO $db): void
    {
        // Optional per migration.
    }
}
