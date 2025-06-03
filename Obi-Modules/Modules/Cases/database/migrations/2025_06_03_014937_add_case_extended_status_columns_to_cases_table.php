<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'cases_db';   // igual que la tabla base

    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {

            /* ───────── sub-estados por etapa ───────── */
            $table->enum('signature_status',
                ['generado','enviado_a_acepta','notificado','contrato_pendiente','mandato_pendiente','documentos firmados']
            )->default('generado')->after('state');

            $table->enum('visit_status',
                ['pendiente','en_proceso','realizado']
            )->default('pendiente')->after('signature_status');

            $table->enum('budget_status',
                ['pendiente','en_proceso','realizado']
            )->default('pendiente')->after('visit_status');

            /* ───────── resultado de liquidación ───────── */
            $table->enum('decision_result',
                ['aprobado','bajo_deducible','rechazado_aseguradora','rechazado_liquidadora','impugnado']
            )->nullable()->after('budget_status');

            /* ───────── situación de pago ───────── */
            $table->enum('payment_status',
                ['pendiente','cobranza','parcialmente_pagado','pagado','cobranza_online']
            )->default('pendiente')->after('decision_result');

            /* ───────── estado global del caso ───────── */
            $table->enum('overall_status',
                ['abierto','con_pendientes','cerrado']
            )->default('abierto')->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'signature_status',
                'visit_status',
                'budget_status',
                'decision_result',
                'payment_status',
                'overall_status',
            ]);
        });
    }
};
