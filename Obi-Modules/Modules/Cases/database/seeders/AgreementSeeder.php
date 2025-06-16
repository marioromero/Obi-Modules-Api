<?php

namespace Modules\Cases\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgreementSeeder extends Seeder
{
    public function run(): void
    {
        // Inserta un registro inicial con nombre "CODELCO"
        DB::table('agreements')->insert([
            ['name' => 'CODELCO'],
        ]);
    }
}
