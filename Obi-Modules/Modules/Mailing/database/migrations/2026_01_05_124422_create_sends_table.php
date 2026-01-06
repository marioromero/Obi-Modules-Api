<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mailing_db';

    public function up(): void
    {
        Schema::create('sends', function (Blueprint $table) {
            $table->id();
            $table->dateTime('sent_at');
            $table->string('status', 20);
            $table->unsignedBigInteger('email_schedule_id');
            $table->string('email', 100);
            $table->unsignedBigInteger('customer_detail_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sends');
    }
};
