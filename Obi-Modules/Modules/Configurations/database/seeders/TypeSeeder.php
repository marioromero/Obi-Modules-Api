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
        DB::table('types')->insert([
            ['name' => 'Global_geography'],
            ['name' => 'User_filter'],
        ]);
    }
}
