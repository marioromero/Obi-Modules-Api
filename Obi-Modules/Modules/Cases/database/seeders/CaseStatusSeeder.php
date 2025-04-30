<?php

namespace Modules\Cases\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaseStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('case_statuses')->insert([
            ['name' => 'Aprobado'],
            ['name' => 'Bajo deducible'],
            ['name' => 'Cobranza'],
            ['name' => 'Cobranza Online'],
            ['name' => 'Documentos Firmados'],
            ['name' => 'Denuncio Realizado'],
            ['name' => 'Desistido Sin Visita'],
            ['name' => 'Desistido'],
            ['name' => 'Deudor'],
            ['name' => 'En Progreso'],
            ['name' => 'Impugnado'],
            ['name' => 'Pagado'],
            ['name' => 'Presupuesto Enviado'],
            ['name' => 'Rechazado'],
            ['name' => 'Sin Estado'],
            ['name' => 'Visita Realizada'],
            ['name' => 'Contrato Pendiente'],
            ['name' => 'Mandato Pendiente'],
        ]);
    }
}
