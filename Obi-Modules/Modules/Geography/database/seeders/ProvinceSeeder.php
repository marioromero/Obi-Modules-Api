<?php

namespace Modules\Geography\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('provinces')->insert([
            ['name' => 'Arica', 'region_id' => 1],
            ['name' => 'Parinacota', 'region_id' => 1],

            ['name' => 'Iquique', 'region_id' => 2],
            ['name' => 'Tamarugal', 'region_id' => 2],

            ['name' => 'Antofagasta', 'region_id' => 3],
            ['name' => 'El Loa', 'region_id' => 3],
            ['name' => 'Tocopilla', 'region_id' => 3],

            ['name' => 'Copiapó', 'region_id' => 4],
            ['name' => 'Chañaral', 'region_id' => 4],
            ['name' => 'Huasco', 'region_id' => 4],

            ['name' => 'Elqui', 'region_id' => 5],
            ['name' => 'Limarí', 'region_id' => 5],
            ['name' => 'Choapa', 'region_id' => 5],

            ['name' => 'Valparaíso', 'region_id' => 6],
            ['name' => 'Marga Marga', 'region_id' => 6],
            ['name' => 'San Antonio', 'region_id' => 6],
            ['name' => 'San Felipe de Aconcagua', 'region_id' => 6],
            ['name' => 'Los Andes', 'region_id' => 6],
            ['name' => 'Petorca', 'region_id' => 6],
            ['name' => 'Quillota', 'region_id' => 6],
            ['name' => 'Isla de Pascua', 'region_id' => 6],

            ['name' => 'Santiago', 'region_id' => 7],
            ['name' => 'Chacabuco', 'region_id' => 7],
            ['name' => 'Cordillera', 'region_id' => 7],
            ['name' => 'Maipo', 'region_id' => 7],
            ['name' => 'Melipilla', 'region_id' => 7],
            ['name' => 'Talagante', 'region_id' => 7],

            ['name' => 'Cachapoal', 'region_id' => 8],
            ['name' => 'Colchagua', 'region_id' => 8],
            ['name' => 'Cardenal Caro', 'region_id' => 8],

            ['name' => 'Talca', 'region_id' => 9],
            ['name' => 'Curicó', 'region_id' => 9],
            ['name' => 'Linares', 'region_id' => 9],
            ['name' => 'Cauquenes', 'region_id' => 9],

            ['name' => 'Diguillín', 'region_id' => 10],
            ['name' => 'Itata', 'region_id' => 10],
            ['name' => 'Punilla', 'region_id' => 10],

            ['name' => 'Concepción', 'region_id' => 11],
            ['name' => 'Arauco', 'region_id' => 11],
            ['name' => 'Biobío', 'region_id' => 11],

            ['name' => 'Cautín', 'region_id' => 12],
            ['name' => 'Malleco', 'region_id' => 12],

            ['name' => 'Valdivia', 'region_id' => 13],
            ['name' => 'Ranco', 'region_id' => 13],

            ['name' => 'Llanquihue', 'region_id' => 14],
            ['name' => 'Osorno', 'region_id' => 14],
            ['name' => 'Chiloé', 'region_id' => 14],
            ['name' => 'Palena', 'region_id' => 14],

            ['name' => 'Coyhaique', 'region_id' => 15],
            ['name' => 'Aysén', 'region_id' => 15],
            ['name' => 'Capitán Prat', 'region_id' => 15],
            ['name' => 'General Carrera', 'region_id' => 15],

            ['name' => 'Magallanes', 'region_id' => 16],
            ['name' => 'Tierra del Fuego', 'region_id' => 16],
            ['name' => 'Última Esperanza', 'region_id' => 16],
            ['name' => 'Antártica Chilena', 'region_id' => 16],
        ]);
    }
}



