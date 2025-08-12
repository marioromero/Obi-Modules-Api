<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** La vista vive en la conexión customers_db */
    protected $connection = 'customers_db';

    public function up(): void
    {
        /* Nombres exactos de las bases, leídos del config */
        $customersDb = config('database.connections.customers_db.database'); // grupoint_obi_customers_qa
        $geographyDb = config('database.connections.geography_db.database'); // grupoint_obi_geography_qa
        $traroDb     = config('database.connections.traro_db.database');     // grupoint_traro_QA

        DB::connection('customers_db')->statement(<<<SQL
        CREATE OR REPLACE VIEW v_customers_details AS
        SELECT
            c.*,                                        -- todos los campos originales

            /* ── nombres legibles ─────────────────── */
            cm.name  AS commune_name,
            u.name   AS user_name                       -- nombre del agente asignado

        FROM {$customersDb}.customers               c

        /* comuna (geography_db) */
        LEFT JOIN {$geographyDb}.communes cm
               ON cm.id = c.commune_id

        /* agente asignado (traro_db) */
        LEFT JOIN {$traroDb}.users u
               ON u.id = c.assigned_agent;
        SQL);
    }

    public function down(): void
    {
        // Si prefieres, puedes usar un DROP VIEW explícito:
        // DB::connection($this->connection)->statement('DROP VIEW IF EXISTS v_customers_details');
        Schema::connection($this->connection)->dropIfExists('v_customers_details');
    }
};
