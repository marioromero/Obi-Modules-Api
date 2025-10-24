<?php

namespace Modules\Users\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModelLogsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('model_logs')->insert([
            ['name' => 'case'],
            ['name' => 'customer'],
            ['name' => 'user'],
            ['name' => 'bank'],
            ['name' => 'insurer'],
            ['name' => 'loss_adjuster'],
            ['name' => 'configuration'],
            ['name' => 'accident_type'],
            ['name' => 'auth'],
        ]);
    }
}
