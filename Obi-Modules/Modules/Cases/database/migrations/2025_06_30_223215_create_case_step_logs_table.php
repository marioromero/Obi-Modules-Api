<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'cases_db';

    public function up(): void
    {
        Schema::connection('cases_db')->create('case_step_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $t->string('from_state', 120)->nullable();
            $t->string('to_state', 120);
            $t->string('from_sub', 120)->nullable();
            $t->string('to_sub', 120)->nullable();
            $t->enum('type', ['state', 'sub_state'])->default('state');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->json('payload')->nullable();
            $t->longText('comments')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index('case_id');
            $t->index('to_state');
        });
    }

    public function down(): void
    {
        Schema::connection('cases_db')->dropIfExists('case_step_logs');
    }
};