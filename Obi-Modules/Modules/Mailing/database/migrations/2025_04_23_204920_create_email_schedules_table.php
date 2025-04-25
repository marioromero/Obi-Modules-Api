<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mailing_db';

    public function up(): void
    {
        Schema::create('email_schedules', function (Blueprint $table) {
            $table->id();
            $table->dateTime('start_in');
            $table->boolean('send_once');
            $table->integer('send_frecuency_days')->nullable();
            $table->foreignId('customer_set')->constrained('customers_sets');
            $table->foreignId('email_template')->constrained('email_templates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_schedules');
    }
};
