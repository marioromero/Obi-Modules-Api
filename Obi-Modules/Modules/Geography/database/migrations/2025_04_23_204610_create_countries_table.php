<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'geography_db';

    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('demonym_male', 20);
            $table->string('demonym_female', 20);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
