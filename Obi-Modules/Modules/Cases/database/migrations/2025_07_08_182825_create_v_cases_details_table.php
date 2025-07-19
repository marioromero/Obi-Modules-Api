<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** La vista pertenece a la conexión cases_db */
    protected $connection = 'cases_db';

    public function up(): void
    {
        /* Nombre exacto de la BD Traro, tomado de la conexión */
        $traroDb = config('database.connections.traro_db.database');   // => grupoint_traro_QA

        DB::connection('cases_db')->statement(<<<SQL
        CREATE OR REPLACE VIEW v_cases_details AS
        SELECT
            c.*,                                   -- columnas de cases

            /* ───────── Cliente ───────── */
            CONCAT_WS(' ', cu.name, cu.lastname) AS customer_name,
            cu.dni                               AS customer_dni,
            cu.address                           AS customer_address,
            cmu.name                             AS customer_commune_name,

            /* ───────── Catálogos externos ───────── */
            b.name   AS bank_name,
            ins.name AS insurer_name,
            la.name  AS loss_adjuster_name,
            p.name   AS priority_name,
            ag.name  AS agreement_name,
            at.name  AS accident_type_name,

            /* ───────── Usuarios (desde TRARO) ───────── */
            agnt.name AS agent_name,            -- role_id = 2
            cons.name AS consultant_name,       -- role_id = 5
            asg.name  AS assigned_user_name,
            crt.name  AS created_by_name,

            /* Comuna directa del caso (si existe) */
            cco.name AS commune_name

        FROM grupoint_obi_cases_qa.cases            c
        LEFT JOIN grupoint_obi_customers_qa.customers cu ON cu.id = c.customer_id
        LEFT JOIN grupoint_obi_geography_qa.communes  cmu ON cmu.id = cu.commune_id

        /* ───────── Banks ───────── */
        LEFT JOIN grupoint_obi_banks_qa.banks          b  ON b.id  = c.bank_id
        LEFT JOIN grupoint_obi_banks_qa.insurers       ins ON ins.id = c.insurer_id
        LEFT JOIN grupoint_obi_banks_qa.loss_adjusters la  ON la.id = c.loss_adjuster_id

        /* ───────── Catálogos internos ───────── */
        LEFT JOIN grupoint_obi_cases_qa.priorities      p  ON p.id  = c.priority_id
        LEFT JOIN grupoint_obi_cases_qa.agreements      ag ON ag.id = c.agreement_id
        LEFT JOIN grupoint_obi_cases_qa.accident_types  at ON at.id = c.accident_type_id

        /* ───────── Usuarios (BD TRARO) ───────── */
        LEFT JOIN {$traroDb}.users agnt ON agnt.id = c.agent_id       AND agnt.role_id = 2
        LEFT JOIN {$traroDb}.users cons ON cons.id = c.consultant_id  AND cons.role_id = 5
        LEFT JOIN {$traroDb}.users asg  ON asg.id  = c.assigned_user
        LEFT JOIN {$traroDb}.users crt  ON crt.id  = c.created_by

        /* ───────── Comuna directa del caso ───────── */
        LEFT JOIN grupoint_obi_geography_qa.communes cco ON cco.id = c.commune_id;
        SQL);
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('v_cases_details');
    }
};
