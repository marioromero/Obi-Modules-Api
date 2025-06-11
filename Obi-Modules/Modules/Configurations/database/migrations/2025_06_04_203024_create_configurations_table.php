<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'configurations_db';

    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->longText('content');
            $table->foreignId('type_id')->constrained('types'); //FK a types
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};

