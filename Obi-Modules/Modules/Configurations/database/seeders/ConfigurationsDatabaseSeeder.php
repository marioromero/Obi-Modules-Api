<?php

namespace Modules\Configurations\database\seeders;

use Illuminate\Database\Seeder;

class ConfigurationsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TypeSeeder::class,           // inserta los tipos (Global_geography, User_filter)
            ConfigurationSeeder::class,  // inserta el JSON con los 85 IDs
        ]);
    }
}
