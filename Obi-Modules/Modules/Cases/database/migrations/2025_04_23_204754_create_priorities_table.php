<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'cases_db';

    public function up(): void
    {
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};

