<?php

namespace Modules\Users\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('events')->insert([
            // Casos
            ['name' => 'create_case'],
            ['name' => 'update_case'],
            ['name' => 'delete_case'],

            // Clientes
            ['name' => 'create_customer'],
            ['name' => 'update_customer'],
            ['name' => 'delete_customer'],

            // Usuarios
            ['name' => 'create_user'],
            ['name' => 'update_user'],
            ['name' => 'delete_user'],

            // Bancos
            ['name' => 'create_bank'],
            ['name' => 'update_bank'],
            ['name' => 'delete_bank'],

            // Aseguradoras
            ['name' => 'create_insurer'],
            ['name' => 'update_insurer'],
            ['name' => 'delete_insurer'],

            // Liquidadoras
            ['name' => 'create_loss_adjuster'],
            ['name' => 'update_loss_adjuster'],
            ['name' => 'delete_loss_adjuster'],

            // Configuraciones
            ['name' => 'create_configuration'],
            ['name' => 'update_configuration'],
            ['name' => 'delete_configuration'],

            // Tipos de siniestro
            ['name' => 'create_accident_type'],
            ['name' => 'update_accident_type'],
            ['name' => 'delete_accident_type'],

            // Autenticación
            ['name' => 'login_success'],
            ['name' => 'login_failed'],
        ]);
    }
}
