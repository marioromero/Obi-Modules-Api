<?php

namespace Modules\Banks\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LossAdjusterSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => 'Yurac'],
            ['name' => 'consorcio'],
            ['name' => 'FGR'],
            ['name' => 'Beckers'],
            ['name' => 'Liberty'],
            ['name' => 'BFV'],
            ['name' => 'Mclarens'],
            ['name' => 'Faisal Mohor'],
            ['name' => 'SeguRed'],
            ['name' => 'JPV'],
            ['name' => 'Machard'],
            ['name' => 'Nuevo Mundo'],
            ['name' => 'Beckett'],
            ['name' => 'Charles Taylor'],
            ['name' => 'Viollier'],
            ['name' => 'Apluss'],
            ['name' => 'Socal Ltda'],
            ['name' => 'Madrigal Swain (MS365)'],
            ['name' => 'TBR Liquidadores'],
            ['name' => 'Addvalora Global'],
            ['name' => 'SGC Liquidadores'],
            ['name' => 'Cooper'],
            ['name' => 'Barbuss'],
            ['name' => 'RTS liquidadores'],
            ['name' => 'Nexus'],
            ['name' => 'WT liquidadores'],
        ];

        foreach ($records as $item) {
            DB::table('loss_adjusters')->insertOrIgnore($item);
        }
    }
}
