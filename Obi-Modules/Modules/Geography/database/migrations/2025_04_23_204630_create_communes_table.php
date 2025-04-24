<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'geography_db';

    public function up(): void
    {
        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('province_id')->constrained('provinces');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communes');
    }
};
