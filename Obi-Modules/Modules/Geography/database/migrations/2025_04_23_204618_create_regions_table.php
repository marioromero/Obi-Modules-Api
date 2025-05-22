<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'geography_db';

    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('country_id')->constrained('countries');
            $table->foreignId('version_id')->constrained('version_p_a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};

