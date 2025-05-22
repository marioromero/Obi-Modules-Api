<?php

namespace Modules\Cases\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrioritySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('priorities')->insert([
            ['name' => 'Alta'],
            ['name' => 'Media'],
            ['name' => 'Baja'],
        ]);
    }
}



