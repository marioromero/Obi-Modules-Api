<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'users_db';

    public function up(): void
    {
        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('timestamp')->useCurrent();
            $table->longText('details');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('model_id');
            $table->unsignedBigInteger('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logs');
    }
};

