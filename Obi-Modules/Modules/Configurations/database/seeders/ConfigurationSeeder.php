<?php

namespace Modules\Configurations\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigurationSeeder extends Seeder
{

    public function run(): void
    {

        // 1) Generar los arrays de IDs para países y provincias

        $countryIds  = range(1, 85);  // [1, 2, …, 85]
        $provinceIds = range(1, 56);  // [1, 2, …, 56]

        // 2) Actualizar (o insertar) la configuración de Global_geography (type_id = 2)
        //    El content ahora contendrá dos arrays anidados: 'countries' y 'provinces'

        DB::table('configurations')->updateOrInsert(
            ['type_id' => 2],
            ['content' => json_encode([
                'countries' => $countryIds,
                'provinces'=> $provinceIds,
            ], JSON_UNESCAPED_UNICODE)]
        );

        // 3) Definir e insertar la configuración de Global_settings (type_id = 3)
        //    Aquí metemos un array de textos con los posibles estados civiles

        $maritalStatuses = [
            'Casada', 'Casado', 'Conviviente Civil',
            'Divorciada', 'Divorciado',
            'Separada', 'Separado',
            'Soltera', 'Soltero',
            'Unión Civil',
            'Viuda', 'Viudo'
        ];

        DB::table('configurations')->updateOrInsert(
            ['type_id' => 3],
            ['content' => json_encode([
                'marital_statuses' => $maritalStatuses,
            ], JSON_UNESCAPED_UNICODE)]
        );

        // 4) Definir e insertar la configuración de User_responsabilities (type_id = 4)
        //    Mapea cada estado general de caso a su array de usuarios encargados
        $userResponsibilities = [
            'Denuncio'      => ['user_assigned' => [27]],
            'Programacion'  => ['user_assigned' => [28]],
            'Visita'        => ['user_assigned' => [21, 22]],
            'Presupuesto'   => ['user_assigned' => [21]],
            'Liquidacion'   => ['user_assigned' => [21]],
            'Recaudacion'   => ['user_assigned' => [27]],
        ];

        DB::table('configurations')->updateOrInsert(
            ['type_id' => 4],
            ['content' => json_encode($userResponsibilities, JSON_UNESCAPED_UNICODE)]
        );
    }
}
