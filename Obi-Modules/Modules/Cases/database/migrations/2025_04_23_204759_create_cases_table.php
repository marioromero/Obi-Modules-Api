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
            $table->string('code', 12)->nullable(); // codigo (titulo TR1234)
             $table->boolean('sent_to_acepta')->default(false); // Enviado a acepta?
            $table->unsignedBigInteger('priority_id')->default(1); // prioridad
            $table->dateTime('created_at')->useCurrent(); // fecha creacion
            $table->foreignId('agreement_id')->nullable()->constrained('agreements'); // convenio
            $table->string('property_address', 255); // direccion de la propiedad
            $table->date('inspection_date')->nullable(); // fecha visita
            $table->date('document_signing_date')->nullable(); // fecha firma de documentos
            $table->date('complaint_date')->nullable(); // fecha de denuncio
            $table->date('collection_date')->nullable(); // fecha cobranza
            $table->date('budget_sending_date')->nullable(); // fecha envio presupuesto
            $table->date('settlement_report_date')->nullable(); // fecha informe de liquidacion
            $table->date('probable_payment_date')->nullable(); // fecha probable de pago
            $table->date('online_collection_date')->nullable(); // fecha cobranza online
            $table->integer('accident_number')->nullable(); // numero de siniestro
            $table->integer('bank_service_number')->nullable(); // numero de atencion
            $table->integer('advisory_amount')->nullable(); // monto asesoria
            $table->foreignId('accident_type_id')->nullable()->constrained('accident_types'); // tipo de siniestro

            /* --- Campos transversales mínimos --- */
            $table->boolean('is_duplicated')->default(false); // duplicado?
            $table->text('description')->nullable(); // descripcion
            $table->string('resolution')->nullable(); // resolucion

            /* ────── Datos core del negocio Traro ────── */
            $table->date('date_of_loss')->nullable(); //fecha del siniestro
            $table->string('property_type', 30); //tipo de propiedad
            $table->date('contestation_date')->nullable(); //fecha de impugnacion

            /* --- Relaciones externas--- */
            $table->unsignedBigInteger('customer_id')->nullable(); //cliente
            $table->unsignedBigInteger('created_by')->nullable(); // creado por
            $table->unsignedBigInteger('assigned_user')->nullable(); // usuario asignado
            $table->unsignedBigInteger('commune_id')->nullable(); // comuna
            $table->unsignedBigInteger('consultant_id')->nullable(); // asesor

            /* Montos específicos */
            $table->integer('approved_amount')->nullable();
            $table->float('uf_approved', 10, 2)->nullable();
            $table->integer('amount_owed')->nullable();
            $table->integer('amount_paid')->nullable();

            /* Relaciones con banco, aseguradora y liquidadora */
            $table->unsignedBigInteger('bank_id')->nullable(); // FK to banks_db.banks
            $table->unsignedBigInteger('insurer_id')->nullable(); // FK to banks_db.insurers
            $table->unsignedBigInteger('loss_adjuster_id')->nullable(); // FK to banks_db.loss_adjusters
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
