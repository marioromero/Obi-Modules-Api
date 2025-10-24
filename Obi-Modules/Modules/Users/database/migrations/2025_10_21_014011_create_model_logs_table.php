<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'users_db';

    public function up(): void
    {
        Schema::create('model_logs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_logs');
    }
};
