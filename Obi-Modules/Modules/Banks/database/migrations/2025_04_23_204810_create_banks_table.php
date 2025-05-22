<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'banks_db';

    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->boolean('is_visible')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};

