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
            $table->dateTime('ending_at');
            $table->integer('send_once')->default(25); // 25 seg es el intervalo de envio de cada correo
            $table->integer('sends_ok')->default(0);
            $table->integer('failed_or_pendings')->default(0);
            $table->boolean('is_retry')->default(false);
            $table->string('status', 32)->default('pending'); // pending, in_progress, paused, finished, canceled
            $table->boolean('only_business_days')->default(false);
            $table->unsignedBigInteger('user_id'); // creador (Auth o payload)
            $table->unsignedBigInteger('customer_set_id');
            $table->unsignedBigInteger('email_template_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_schedules');
    }
};
