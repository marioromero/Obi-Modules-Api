<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'schedules_db';

    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->dateTime('schedule_date');
            $table->unsignedBigInteger('scheduling_user');    // FK to users_db.users
            $table->unsignedBigInteger('case_id');            // FK to cases_db.cases
            $table->unsignedBigInteger('scheduled_user');     // FK to users_db.users
            $table->foreignId('schedule_status_id')->constrained('schedule_statuses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
