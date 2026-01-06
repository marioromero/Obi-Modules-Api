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
            $table->integer('send_once'); // 25 seg es el intervalo de envio
            $table->integer('failed_or_pendings')->default(0);
            $table->boolean('is_retry')->default(false);
            $table->unsignedBigInteger('customer_set_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_schedules');
    }
};
