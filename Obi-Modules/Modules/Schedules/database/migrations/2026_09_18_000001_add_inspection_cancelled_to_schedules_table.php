<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'schedules_db';

    public function up(): void
    {
        Schema::connection('schedules_db')->table('schedules', function (Blueprint $t) {
            $t->boolean('inspection_cancelled')->default(false)->after('inspection_failed'); // Visita cancelada?
        });
    }

    public function down(): void
    {
        Schema::connection('schedules_db')->table('schedules', function (Blueprint $t) {
            $t->dropColumn('inspection_cancelled');
        });
    }
};
