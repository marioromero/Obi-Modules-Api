<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'cases_db';

    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->longText('content');
            $table->dateTime('created_at');
            $table->unsignedBigInteger('case_id');       // FK to cases_db.cases
            $table->unsignedBigInteger('user_id');       // FK to users_db.users
            $table->unsignedBigInteger('response_from')->nullable(); // FK to comments.id (auto FK)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};

