<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'cases_db';

    public function up(): void
    {
        Schema::connection('cases_db')->table('cases', function (Blueprint $t) {
            $t->enum('signature_status', ['generado','enviado a acepta','notificado','contrato pendiente','mandato pendiente','firmados'])->default('generado');
            $t->enum('denounce_status', ['pendiente','en proceso','realizado'])->nullable();
            $t->enum('scheduling_status', ['pendiente','en proceso','realizado'])->nullable();
            $t->enum('visit_status', ['pendiente','en proceso','realizado'])->nullable();
            $t->enum('budget_status', ['pendiente','en proceso','realizado'])->nullable();
            $t->enum('decision_status', ['en espera','aprobado','bajo deducible','rechazado aseguradora','rechazado liquidadora','impugnado'])->nullable();
            $t->enum('payment_status', ['pendiente','cobranza','parcialmente pagado','pagado','cobranza online'])->nullable();
            $t->enum('overall_status', ['en proceso','con pendientes','cerrado'])->default('en proceso');
        });
    }

    public function down(): void
    {
        Schema::connection('cases_db')->table('cases', function (Blueprint $t) {
            $t->dropColumn(['signature_status', 'denounce_status', 'scheduling_status', 'visit_status', 'budget_status', 'decision_status', 'payment_status', 'overall_status']);
        });
    }
};