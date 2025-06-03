<?php

namespace Modules\Banks\database\seeders;

use Illuminate\Database\Seeder;

class BanksDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            BankSeeder::class,
            InsurerSeeder::class,
            LossAdjusterSeeder::class,
        ]);
    }
}



