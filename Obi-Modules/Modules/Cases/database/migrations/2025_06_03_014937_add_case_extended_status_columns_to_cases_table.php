<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'cases_db';   // igual que la tabla base

    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {

            /* ────── Datos core del negocio Traro ────── */
            $table->date('date_of_loss')->nullable()->after('state');
            $table->string('property_type', 30)->after('date_of_loss');
            $table->date('contestation_date')->nullable()->after('property_type');

            /* Montos específicos */
            $table->integer('approved_amount')->nullable()->after('contestation_date');
            $table->float('uf_approved', 10, 2)->nullable()->after('approved_amount');
            $table->integer('amount_owed')->nullable()->after('uf_approved');
            $table->integer('amount_paid')->nullable()->after('amount_owed');

            /* Relaciones con banco, aseguradora y liquidadora */
            $table->unsignedBigInteger('bank_id')->nullable(); // FK to banks_db.banks
            $table->unsignedBigInteger('insurer_id')->nullable(); // FK to banks_db.insurers
            $table->unsignedBigInteger('loss_adjuster_id')->nullable(); // FK to banks_db.loss_adjusters

            /* Relaciones propias */
            $table->unsignedBigInteger('agent_id')->after('assigned_user');

            /* ────── Sub-estados por paso ────── */
            $table->enum('signature_status', [
                'generado', 'enviado_a_acepta', 'notificado',
                'contrato_pendiente', 'mandato_pendiente', 'firmados',
            ])->default('generado')->after('amount_paid');

            $table->enum('denounce_status', ['pendiente', 'en_proceso', 'realizado'])
                ->default('pendiente')->after('signature_status');

            $table->enum('scheduling_status', ['pendiente', 'en_proceso', 'realizado'])
                ->default('pendiente')->after('denounce_status');

            $table->enum('visit_status', [
                'pendiente', 'en_proceso', 'realizado',
            ])->default('pendiente')->after('signature_status');

            $table->enum('budget_status', [
                'pendiente', 'en_proceso', 'realizado',
            ])->default('pendiente')->after('visit_status');

            /* Liquidación */
            $table->enum('decision_result', [
                'aprobado', 'bajo_deducible',
                'rechazado_aseguradora', 'rechazado_liquidadora',
                'impugnado',
            ])->nullable()->after('budget_status');

            /* Recaudación */
            $table->enum('payment_status', [
                'pendiente', 'cobranza', 'parcialmente_pagado',
                'pagado', 'cobranza_online',
            ])->default('pendiente')->after('decision_result');

            /* Estado global */
            $table->enum('overall_status', [
                'abierto', 'con_pendientes', 'cerrado',
            ])->default('abierto')->after('payment_status');
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
