<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'cases_db';

    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->unsignedBigInteger('priority_id');
            $table->dateTime('created_at');
            $table->string('property_type', 30);
            $table->string('state', 40)->default('Draft');        // manejado por Spatie

            $table->integer('approved_amount')->nullable();
            $table->integer('amount_owed')->nullable();
            $table->integer('amount_paid')->nullable();

            $table->unsignedBigInteger('customer_id');            // customers_db
            $table->unsignedBigInteger('agent_id');               // users_db
            $table->unsignedBigInteger('created_by');             // users_db

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
