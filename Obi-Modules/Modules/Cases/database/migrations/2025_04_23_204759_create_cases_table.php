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

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
