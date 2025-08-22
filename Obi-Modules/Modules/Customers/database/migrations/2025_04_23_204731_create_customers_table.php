<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'customers_db';

    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('lastname', 100)->nullable();
            $table->string('full_name', 100)->nullable(); // ← NUEVO
            $table->string('dni', 20)->nullable()->unique();
            $table->string('username', 50)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('phone2', 15)->nullable();
            $table->char('gender', 1)->nullable();
            $table->string('marital_status', 50)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('nationality', 50)->nullable();
            $table->json('tags')->nullable();
            $table->unsignedBigInteger('commune_id')->nullable();
            $table->unsignedBigInteger('assigned_agent')->nullable();
            $table->longText('comments')->nullable();
        });
    }

           public function down(): void
           {
               Schema::dropIfExists('customers');
           }
       };
       
