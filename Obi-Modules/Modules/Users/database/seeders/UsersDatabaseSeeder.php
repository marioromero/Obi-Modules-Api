<?php

namespace Modules\Users\database\seeders;

use Illuminate\Database\Seeder;

class UsersDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserStatusSeeder::class,
            TypeSeeder::class,
            ConfigurationSeeder::class,
            EventSeeder::class,
            UserSeeder::class,
            UserLogSeeder::class,
        ]);
    }
}



