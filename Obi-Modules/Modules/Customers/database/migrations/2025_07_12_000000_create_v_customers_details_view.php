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
            u.name   AS user_name,
            cs.name  AS case_status_name

        FROM {$customersDb}.customers               c

        /* comuna (geography_db) */
        LEFT JOIN {$geographyDb}.communes cm
               ON cm.id = c.commune_id

        /* usuario creador / responsable (traro_db) */
        LEFT JOIN {$traroDb}.users u
               ON u.id = c.user_id

        /* status de cliente (en la misma BD cases_db, si aplica) */
        LEFT JOIN grupoint_obi_cases_qa.case_statuses cs
               ON cs.id = c.case_status_id;
        SQL);
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('v_customers_details');
    }
};
