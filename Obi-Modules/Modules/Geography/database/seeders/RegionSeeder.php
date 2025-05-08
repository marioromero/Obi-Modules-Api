<?php

namespace Modules\Geography\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('regions')->insert([
            ['name' => 'Arica y Parinacota', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Tarapacá', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Antofagasta', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Atacama', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Coquimbo', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Valparaíso', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Metropolitana de Santiago', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Libertador General Bernardo O’Higgins', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Maule', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Ñuble', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Biobío', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'La Araucanía', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Los Ríos', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Los Lagos', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Aysén del General Carlos Ibáñez del Campo', 'country_id' => 9, 'version_id' => 1],
            ['name' => 'Magallanes', 'country_id' => 9, 'version_id' => 1],
        ]);
    }
}
