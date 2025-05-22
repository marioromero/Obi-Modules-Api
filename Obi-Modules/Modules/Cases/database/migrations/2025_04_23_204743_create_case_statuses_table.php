<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'cases_db';

    public function up(): void
    {
        Schema::create('case_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_statuses');
    }
};

