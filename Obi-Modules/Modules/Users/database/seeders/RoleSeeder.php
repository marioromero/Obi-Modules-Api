<?php

namespace Modules\Users\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['name' => 'Administrador'],
            ['name' => 'Coordinador'],
            ['name' => 'Ejecutivo'],
            ['name' => 'Administrativo'],
            ['name' => 'Asesor'],
        ]);
    }
}



