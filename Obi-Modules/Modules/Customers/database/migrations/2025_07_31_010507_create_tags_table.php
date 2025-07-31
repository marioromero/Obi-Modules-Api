<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    protected $connection = 'customers_db';

    public function up(): void
    {
        Schema::connection('customers_db')->create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('color');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::connection('customers_db')->dropIfExists('tags');
    }
};
