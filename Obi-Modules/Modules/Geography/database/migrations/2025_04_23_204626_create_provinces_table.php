<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'geography_db';

    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('region_id')->constrained('regions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
