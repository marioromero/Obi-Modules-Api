<?php

namespace Modules\Configurations\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::connection('configurations_db')->table('types')->insert([
            ['name' => 'User_filters'],
            ['name' => 'Global_geography'],
            ['name' => 'Global_settings'],
            ['name' => 'User_responsabilities'],
            ['name' => 'Columns_by_rol'],
            ['name' => 'States_machine'],
            ['name' => 'Agent_available'],
            ['name' => 'Consultant_available'],
            ['name' => 'User_charts'],
        ]);
    }
}
