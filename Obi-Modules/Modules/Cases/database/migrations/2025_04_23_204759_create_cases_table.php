<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'cases_db';

    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            /* --- PK y claves básicas --- */
            $table->id();
            $table->string('code', 12)->unique();
            $table->unsignedBigInteger('priority_id');
            $table->dateTime('created_at');
            $table->string('state', 40)->default('Draft');

            /* --- Campos transversales mínimos --- */
            $table->boolean('is_duplicated')->default(false);
            $table->text('description')->nullable();
            $table->string('resolution')->nullable();

            /* --- Relaciones genéricas --- */
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_user');

            /* ────── Datos core del negocio Traro ────── */
            $table->date('date_of_loss')->nullable();
            $table->string('property_type', 30);
            $table->date('contestation_date')->nullable();

            /* Montos específicos */
            $table->integer('approved_amount')->nullable();
            $table->float('uf_approved', 10, 2)->nullable();
            $table->integer('amount_owed')->nullable();
            $table->integer('amount_paid')->nullable();

            /* Relaciones con banco, aseguradora y liquidadora */
            $table->unsignedBigInteger('bank_id')->nullable(); // FK to banks_db.banks
            $table->unsignedBigInteger('insurer_id')->nullable(); // FK to banks_db.insurers
            $table->unsignedBigInteger('loss_adjuster_id')->nullable(); // FK to banks_db.loss_adjusters

            /* Relaciones propias */
            $table->unsignedBigInteger('agent_id');

            /* ────── Sub-estados por paso ────── */
            $table->enum('signature_status', [
                'generado', 'enviado_a_acepta', 'notificado',
                'contrato_pendiente', 'mandato_pendiente', 'firmados',
            ])->default('generado');

            $table->enum('denounce_status', ['pendiente', 'en_proceso', 'realizado'])
                ->default('pendiente');

            $table->enum('scheduling_status', ['pendiente', 'en_proceso', 'realizado'])
                ->default('pendiente');

            $table->enum('visit_status', [
                'pendiente', 'en_proceso', 'realizado',
            ])->default('pendiente');

            $table->enum('budget_status', [
                'pendiente', 'en_proceso', 'realizado',
            ])->default('pendiente');

            /* Liquidación */
            $table->enum('decision_result', [
                'aprobado', 'bajo_deducible',
                'rechazado_aseguradora', 'rechazado_liquidadora',
                'impugnado',
            ])->nullable();

            /* Recaudación */
            $table->enum('payment_status', [
                'pendiente', 'cobranza', 'parcialmente_pagado',
                'pagado', 'cobranza_online',
            ])->default('pendiente');

            /* Estado global */
            $table->enum('overall_status', [
                'abierto', 'con_pendientes', 'cerrado',
            ])->default('abierto');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
