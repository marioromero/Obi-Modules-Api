<?php

namespace Modules\Geography\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VersionPASeeder extends Seeder
{
    public function run(): void
    {
        DB::table('version_p_a')->insert([
            [
                'id'     => 1,
                'fecha'  => '2018-09-06 00:00:01',
                'activo' => true,
            ],
        ]);
    }
}



