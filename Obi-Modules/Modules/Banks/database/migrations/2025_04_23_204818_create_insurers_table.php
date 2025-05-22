<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'banks_db';

    public function up(): void
    {
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->boolean('is_visible')->default(true);
            $table->foreignId('bank_id')->constrained('banks');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurers');
    }
};

