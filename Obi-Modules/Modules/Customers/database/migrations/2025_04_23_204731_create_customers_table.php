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
            $table->string('dni', 20)->nullable();
            $table->string('username', 50)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('phone2', 15)->nullable();
            $table->char('gender', 1)->nullable();
            $table->string('marital_status', 15)->nullable();
            $table->string('occupation', 100)->nullable();
            $table->unsignedBigInteger('case_status_id')->nullable(); // FK to cases_db.case_statuses
            $table->unsignedBigInteger('commune_id')->nullable(); // FK to geography_db.communes
            $table->unsignedBigInteger('user_id')->nullable(); // FK to users_db.users
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
