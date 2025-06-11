<?php

namespace Modules\Configurations\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Prepara un arreglo con los 85 IDs: [1, 2, 3, …, 85]
        $countryIds = range(1, 85);

        DB::table('configurations')->insert([
            'type_id' => 1,
            'content' => json_encode($countryIds, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
