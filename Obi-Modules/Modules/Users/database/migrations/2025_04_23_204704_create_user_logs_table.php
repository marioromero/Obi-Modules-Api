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
            $table->dateTime('timestamp');
            $table->string('datails', 250);
            $table->unsignedBigInteger('user_id'); // FK to users
            $table->unsignedBigInteger('event_id'); // FK to events
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logs');
    }
};

