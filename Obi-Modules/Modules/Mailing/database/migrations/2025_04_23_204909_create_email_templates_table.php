<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mailing_db';

    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->unsignedBigInteger('user_id'); // FK to users_db.users
             $table->foreignId('department_id')->constrained('departments');
            $table->longText('content');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

