<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'users_db';

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('lastname', 100)->nullable();
            $table->string('dni', 20)->nullable();
            $table->string('username', 50)->nullable();
            $table->string('password', 255);
            $table->string('email', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 15)->nullable();
            $table->char('gender', 1)->nullable();
            $table->foreignId('status_id')->constrained('user_statuses');
            $table->foreignId('role_id')->constrained('roles');
            $table->unsignedBigInteger('commune_id')->nullable(); // FK to geography_db.communes
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

