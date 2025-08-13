<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** La vista se crea en la conexión cases_db */
    protected $connection = 'cases_db';

    public function up(): void
    {
        /* Nombre real de la BD TRARO según .env */
        $traroDb = config('database.connections.traro_db.database');   // ej. grupoint_traro_QA

        DB::connection('cases_db')->statement(<<<SQL
        /* ───────────────────────────────────────────────────────────────
           Vista: v_cases_details
           - Devuelve 1 fila por caso con datos enriquecidos
           - Incluye el ejecutivo (agent) tomado desde el CLIENTE:
             • agent_id   = customers.assigned_agent
             • agent_name = users.name (traro_db)
        ─────────────────────────────────────────────────────────────── */
        CREATE OR REPLACE VIEW v_cases_details AS
        SELECT
            /* ───── Caso ───── */
            c.*,                            -- incluye sent_to_acepta

            /* ───── Datos de flujo (case_flows en TRARO) ───── */
            cf.fecha_firma_contrato,
            cf.fecha_firma_mandato,
            cf.numero_notificaciones_documentos_enviados,
            cf.numero_notificaciones_documento_pendiente,
            cf.notificacion_documento_firmado,
            cf.active_notifications,

            /* ───── Último cambio de estado ───── */
            csl.last_state_change_user_id,
            lusr.name AS last_state_change_user_name,
            csl.last_state_change_at,

            /* ───── Cliente ───── */
            CONCAT_WS(' ', cu.name, cu.lastname) AS customer_name,
            cu.dni         AS customer_dni,
            cu.address     AS customer_address,
            cmu.name       AS customer_commune_name,

            /* Ejecutivo desde el CLIENTE (assigned_agent) */
            cu.assigned_agent AS agent_id,
            aag.name          AS agent_name,

            /* ───── Catálogos externos ───── */
            b.name   AS bank_name,
            ins.name AS insurer_name,
            la.name  AS loss_adjuster_name,

            /* ───── Catálogos internos ───── */
            p.name   AS priority_name,
            ag.name  AS agreement_name,
            at.name  AS accident_type_name,

            /* ───── Usuarios TRARO (otros del caso) ───── */
            cons.name AS consultant_name,
            asg.name  AS assigned_user_name,
            crt.name  AS created_by_name,

            /* ───── Comuna directa del caso ───── */
            cco.name AS commune_name

        FROM grupoint_obi_cases_qa.cases c

        /* ─────────── Joins principales ─────────── */
        LEFT JOIN grupoint_obi_customers_qa.customers cu ON cu.id = c.customer_id
        LEFT JOIN grupoint_obi_geography_qa.communes  cmu ON cmu.id = cu.commune_id

        LEFT JOIN grupoint_obi_banks_qa.banks          b  ON b.id  = c.bank_id
        LEFT JOIN grupoint_obi_banks_qa.insurers       ins ON ins.id = c.insurer_id
        LEFT JOIN grupoint_obi_banks_qa.loss_adjusters la  ON la.id = c.loss_adjuster_id

        LEFT JOIN grupoint_obi_cases_qa.priorities      p  ON p.id  = c.priority_id
        LEFT JOIN grupoint_obi_cases_qa.agreements      ag ON ag.id = c.agreement_id
        LEFT JOIN grupoint_obi_cases_qa.accident_types  at ON at.id = c.accident_type_id

        /* Usuarios TRARO (del caso) — ya no usamos c.agent_id */
        LEFT JOIN {$traroDb}.users cons ON cons.id = c.consultant_id AND cons.role_id = 5
        LEFT JOIN {$traroDb}.users asg  ON asg.id  = c.assigned_user
        LEFT JOIN {$traroDb}.users crt  ON crt.id  = c.created_by

        /* Usuario del assigned_agent (del cliente) */
        LEFT JOIN {$traroDb}.users aag  ON aag.id  = cu.assigned_agent

        /* Comuna directa del caso */
        LEFT JOIN grupoint_obi_geography_qa.communes cco ON cco.id = c.commune_id

        /* ─────────── Case Flows (TRARO) ─────────── */
        LEFT JOIN {$traroDb}.case_flows cf
               ON cf.obi_case_id = c.id

        /* ─────────── Último step-log ─────────── */
        LEFT JOIN (
            SELECT l.case_id,
                   l.user_id    AS last_state_change_user_id,
                   l.created_at AS last_state_change_at
            FROM grupoint_obi_cases_qa.case_step_logs l
            JOIN (
                SELECT case_id, MAX(created_at) AS max_created_at
                FROM grupoint_obi_cases_qa.case_step_logs
                GROUP BY case_id
            ) recent ON recent.case_id = l.case_id
                     AND recent.max_created_at = l.created_at
        ) csl ON csl.case_id = c.id

        /* Nombre del usuario que hizo el último cambio */
        LEFT JOIN {$traroDb}.users lusr ON lusr.id = csl.last_state_change_user_id;
        SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)
          ->statement('DROP VIEW IF EXISTS `v_cases_details`');
    }
};
