<?php

namespace Modules\Geography\database\seeders;

use Illuminate\Database\Seeder;

class GeographyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            VersionPASeeder::class,
            RegionSeeder::class,
            ProvinceSeeder::class,
            CommuneSeeder::class,
        ]);
    }
}



