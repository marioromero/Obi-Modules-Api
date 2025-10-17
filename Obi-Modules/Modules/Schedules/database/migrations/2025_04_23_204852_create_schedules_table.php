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
        });
    }

   public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
