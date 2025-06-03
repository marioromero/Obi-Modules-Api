<?php

namespace Modules\Reports\database\seeders;

use Illuminate\Database\Seeder;

class ReportsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ReportSeeder::class,
        ]);
    }
}



