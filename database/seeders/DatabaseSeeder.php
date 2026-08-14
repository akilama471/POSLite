<?php

declare(strict_types=1);

use Core\Seeders\Seeder;

/**
 * DatabaseSeeder
 *
 * The root seeder. Add all seeders here in dependency order.
 * Run via:  php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(PrivilegeSeeder::class);

        // Add more seeders below as your system grows, e.g.:
        // $this->call(ShopSeeder::class);
        // $this->call(PrivilegeSeeder::class);
        // $this->call(ProductCategorySeeder::class);
    }
}
