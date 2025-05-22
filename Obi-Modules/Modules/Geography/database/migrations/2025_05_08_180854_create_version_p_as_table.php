<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'geography_db';

    public function up(): void
    {
        Schema::create('version_p_a', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha');
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('version_p_a');
    }
};

