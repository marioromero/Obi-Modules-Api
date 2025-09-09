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
            $table->id(); //ID
            $table->unsignedBigInteger('case_id'); //FK a cases_db.cases
            // Datos de agendamiento
            $table->date('inspection_date')->nullable(); //Fecha de visita
            $table->string('inspection_time', 5)->nullable(); //Hora de visita (HH:MM)
            $table->integer('accident_number')->nullable(); //Número de siniestro
            $table->longText('comments')->nullable(); //Comentarios
            //Relaciones externas
            $table->unsignedBigInteger('insurer_id')->nullable(); //FK a banks_db.insurers
            $table->unsignedBigInteger('loss_adjuster_id')->nullable(); //FK a banks_db.loss_adjusters
            $table->unsignedBigInteger('consultant_id')->nullable(); //FK a users_db.users
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
