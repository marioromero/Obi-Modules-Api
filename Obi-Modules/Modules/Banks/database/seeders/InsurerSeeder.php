<?php

namespace Modules\Banks\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsurerSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => 'BCI'],
            ['name' => 'Estado'],
            ['name' => 'Consorcio'],
            ['name' => 'BNP paribas cardif'],
            ['name' => 'Mapfre'],
            ['name' => 'Sura'],
            ['name' => 'Southbridge Cia Seguros S.A.'],
            ['name' => 'BCI seguros'],
            ['name' => 'Zurich'],
            ['name' => 'Southbridge'],
            ['name' => 'Liberty'],
            ['name' => 'Metlife'],
            ['name' => 'Chubb'],
            ['name' => 'Creditu'],
            ['name' => 'HDI Seguros'],
            ['name' => 'FID'],
            ['name' => 'LT.Ajustadores'],
            ['name' => 'Zenit'],
            ['name' => 'Generales Suramericana S.A'],
            ['name' => 'Everets'],
            ['name' => 'Unnio'],
        ];

        foreach ($records as $item) {
            DB::table('insurers')->insertOrIgnore($item);
        }
    }
}
