<?php

namespace Modules\Configurations\database\seeders;

use Illuminate\Database\Seeder;

class ConfigurationsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TypeSeeder::class,           // inserta los tipos (User_filters, Global_geography, Global_settings)
            ConfigurationSeeder::class,  // inserta el JSON con los IDs o cadenas de texto
        ]);
    }
}
