<?php

namespace Modules\Geography\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('regions')->insert([
            ['name' => 'Arica y Parinacota', 'country_id' => 9],
            ['name' => 'Tarapacá', 'country_id' => 9],
            ['name' => 'Antofagasta', 'country_id' => 9],
            ['name' => 'Atacama', 'country_id' => 9],
            ['name' => 'Coquimbo', 'country_id' => 9],
            ['name' => 'Valparaíso', 'country_id' => 9],
            ['name' => 'Metropolitana de Santiago', 'country_id' => 9],
            ['name' => 'Libertador General Bernardo O’Higgins', 'country_id' => 9],
            ['name' => 'Maule', 'country_id' => 9],
            ['name' => 'Ñuble', 'country_id' => 9],
            ['name' => 'Biobío', 'country_id' => 9],
            ['name' => 'La Araucanía', 'country_id' => 9],
            ['name' => 'Los Ríos', 'country_id' => 9],
            ['name' => 'Los Lagos', 'country_id' => 9],
            ['name' => 'Aysén del General Carlos Ibáñez del Campo', 'country_id' => 9],
            ['name' => 'Magallanes', 'country_id' => 9],
        ]);
    }
}
