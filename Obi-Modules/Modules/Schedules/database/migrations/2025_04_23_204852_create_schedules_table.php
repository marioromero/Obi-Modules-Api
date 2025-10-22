<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'schedules_db';

    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id(); // ID programación
            $table->unsignedBigInteger('case_id'); // FK a cases_db.cases
            $table->longText('liquidator_inspector_info')->nullable(); // Datos del inspector de la liquidadora
            $table->date('inspection_date')->nullable(); // fecha de programacion
            $table->string('inspection_time', 5)->nullable(); // Hora de la inspeccion
            $table->longText('comments')->nullable(); // comentarios de la reprogramacion
            $table->longText('comments_programming')->nullable(); // comentarios de la programacion general
            $table->unsignedBigInteger('consultant_id')->nullable(); // FK a users_db.users (asesor)
            $table->unsignedBigInteger('loss_adjuster_id')->nullable(); // FK a banks_db.loss_adjusters (liquidador)
            $table->boolean('message_sent')->default(false); // Indica si se envió notificación al cliente
            $table->boolean('message_confirmed')->default(false); // Indica si el cliente confirmó la visita
        });
    }

   public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
