<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'cases_db';

    public function up(): void
    {
        Schema::connection('cases_db')->table('cases', function (Blueprint $t) {
            $t->string('state', 120)->default('Ingreso')->after('id');
        });
    }

    public function down(): void
    {
        Schema::connection('cases_db')->table('cases', function (Blueprint $t) {
            $t->dropColumn('state');
        });
    }
};