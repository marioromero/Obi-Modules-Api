<?php

namespace Modules\Customers\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tags')->insert([
            ['name' => 'Problematico', 'color' => '#E53E3E', 'is_active' => true],
            ['name' => 'Deudor',       'color' => '#DD6B20', 'is_active' => true],
            ['name' => 'Limitaciones', 'color' => '#38A169', 'is_active' => true],
        ]);
    }
}
