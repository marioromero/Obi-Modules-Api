<?php

namespace Modules\Users\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('user_statuses')->insert([
            ['name' => 'Activo'],
            ['name' => 'Inactivo'],
            ['name' => 'Deshabilitado'],
        ]);
    }
}



