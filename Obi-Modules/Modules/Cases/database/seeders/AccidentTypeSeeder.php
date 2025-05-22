<?php

namespace Modules\Cases\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccidentTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('accident_types')->insert([
            ['name' => 'Filtración'],
            ['name' => 'Incendio'],
            ['name' => 'Riesgos de la naturaleza'],
            ['name' => 'Riesgos de la naturaleza (Nevazón)'],
            ['name' => 'Rotura cañería'],
            ['name' => 'Sismo'],
            ['name' => 'Temporal'],
        ]);
    }
}



