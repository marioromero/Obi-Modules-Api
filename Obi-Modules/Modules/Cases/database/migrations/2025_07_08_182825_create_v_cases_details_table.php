<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** La vista se crea en la conexión 'cases_db' (producción). */
    protected $connection = 'cases_db';

    public function up(): void
    {
        // 1) DROP en statement separado
        DB::connection($this->connection)->statement('DROP VIEW IF EXISTS `v_cases_details`');

        // 2) CREATE en otro statement
        DB::connection($this->connection)->statement(<<<SQL
        CREATE OR REPLACE VIEW `v_cases_details` AS
        SELECT
          /* ───── Caso ───── */
          c.*,

          /* ───── Snapshot del ÚLTIMO case_flow (por caso, MAX(id)) ───── */
          cf_last.fecha_documentos_listos,
          cf_last.contrato_enviado_a_acepta,
          cf_last.fecha_firma_contrato,
          cf_last.mandato_enviado_a_acepta,
          cf_last.fecha_firma_mandato,
          cf_last.numero_notificaciones_documentos_enviados,
          cf_last.numero_notificaciones_documento_pendiente,
          cf_last.notificacion_documento_firmado,

          /* Disponibilidad según configuración States_machine */
          CASE
            WHEN JSON_CONTAINS(
                   JSON_EXTRACT(confsm.content, '$.steps_with_whatsapp_notifications'),
                   JSON_QUOTE(SUBSTRING_INDEX(c.state, '\\\\', -1))
                 )
            THEN 1 ELSE 0
          END AS available_notifications,

          /* active_notifications “capado” por disponibilidad */
          CASE
            WHEN JSON_CONTAINS(
                   JSON_EXTRACT(confsm.content, '$.steps_with_whatsapp_notifications'),
                   JSON_QUOTE(SUBSTRING_INDEX(c.state, '\\\\', -1))
                 )
            THEN cf_last.active_notifications
            ELSE 0
          END AS active_notifications,

          /* ───── Último cambio de estado (step log) ───── */
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

          /* ───── Catálogos externos ───────── */
          b.name   AS bank_name,
          ins.name AS insurer_name,
          la.name  AS loss_adjuster_name,

          /* ───── Catálogos internos ───────── */
          p.name   AS priority_name,
          ag.name  AS agreement_name,
          at.name  AS accident_type_name,

          /* ───── Usuarios TRARO (otros del caso) ───── */
          cons.name AS consultant_name,
          asg.name  AS assigned_user_name,
          crt.name  AS created_by_name,

          /* ───── Comuna directa del caso ───── */
          cco.name AS commune_name,

          /* ───── Agregados JSON ───── */
          sl.step_logs_json,

          /* Solo el ÚLTIMO case_flow como JSON objeto */
          cflj.case_flow_last_json

        FROM grupoint_obi_cases.cases c

        /* ─────────── Joins principales ─────────── */
        LEFT JOIN grupoint_obi_customers.customers cu ON cu.id = c.customer_id
        LEFT JOIN grupoint_obi_geography.communes  cmu ON cmu.id = cu.commune_id

        LEFT JOIN grupoint_obi_banks.banks          b  ON b.id  = c.bank_id
        LEFT JOIN grupoint_obi_banks.insurers       ins ON ins.id = c.insurer_id
        LEFT JOIN grupoint_obi_banks.loss_adjusters la  ON la.id = c.loss_adjuster_id

        LEFT JOIN grupoint_obi_cases.priorities      p  ON p.id  = c.priority_id
        LEFT JOIN grupoint_obi_cases.agreements      ag ON ag.id = c.agreement_id
        LEFT JOIN grupoint_obi_cases.accident_types  at ON at.id = c.accident_type_id

        /* Usuarios TRARO (del caso) */
        LEFT JOIN grupoint_traro.users cons ON cons.id = c.consultant_id AND cons.role_id = 5
        LEFT JOIN grupoint_traro.users asg  ON asg.id  = c.assigned_user
        LEFT JOIN grupoint_traro.users crt  ON crt.id  = c.created_by
        LEFT JOIN grupoint_traro.users aag  ON aag.id  = cu.assigned_agent

        /* Comuna directa del caso */
        LEFT JOIN grupoint_obi_geography.communes cco ON cco.id = c.commune_id

        /* ─────────── Configuración States_machine ─────────── */
        LEFT JOIN grupoint_obi_configurations.types tsm
               ON tsm.name = 'States_machine'
        LEFT JOIN grupoint_obi_configurations.configurations confsm
               ON confsm.type_id = tsm.id

        /* ─────────── ÚLTIMO case_flow por caso (evita duplicar filas) ─────────── */
        LEFT JOIN (
          SELECT cf1.*
          FROM grupoint_traro.case_flows cf1
          JOIN (
            SELECT obi_case_id, MAX(id) AS max_id
            FROM grupoint_traro.case_flows
            GROUP BY obi_case_id
          ) m ON m.obi_case_id = cf1.obi_case_id AND m.max_id = cf1.id
        ) cf_last ON cf_last.obi_case_id = c.id

        /* ─────────── JSON SOLO del último case_flow ─────────── */
        LEFT JOIN (
          SELECT
            cf1.obi_case_id,
            JSON_OBJECT(
              'id', cf1.id,
              'crm_caso_id', cf1.crm_caso_id,
              'obi_case_id', cf1.obi_case_id,
              'fecha_documentos_listos', cf1.fecha_documentos_listos,
              'contrato_enviado_a_acepta', cf1.contrato_enviado_a_acepta,
              'fecha_firma_contrato', cf1.fecha_firma_contrato,
              'mandato_enviado_a_acepta', cf1.mandato_enviado_a_acepta,
              'fecha_firma_mandato', cf1.fecha_firma_mandato,
              'numero_notificaciones_documentos_enviados', cf1.numero_notificaciones_documentos_enviados,
              'numero_notificaciones_documento_pendiente', cf1.numero_notificaciones_documento_pendiente,
              'notificacion_documento_firmado', cf1.notificacion_documento_firmado,
              'active_notifications', cf1.active_notifications,
              'user_id', cf1.user_id
            ) AS case_flow_last_json
          FROM grupoint_traro.case_flows cf1
          JOIN (
            SELECT obi_case_id, MAX(id) AS max_id
            FROM grupoint_traro.case_flows
            GROUP BY obi_case_id
          ) m ON m.obi_case_id = cf1.obi_case_id AND m.max_id = cf1.id
        ) cflj ON cflj.obi_case_id = c.id

        /* ─────────── Último step log (autor/fecha) ─────────── */
        LEFT JOIN (
          SELECT l.case_id,
                 l.user_id    AS last_state_change_user_id,
                 l.created_at AS last_state_change_at
          FROM grupoint_obi_cases.case_step_logs l
          JOIN (
              SELECT case_id, MAX(created_at) AS max_created_at
              FROM grupoint_obi_cases.case_step_logs
              GROUP BY case_id
          ) recent ON recent.case_id = l.case_id
                   AND recent.max_created_at = l.created_at
        ) csl ON csl.case_id = c.id
        LEFT JOIN grupoint_traro.users lusr ON lusr.id = csl.last_state_change_user_id

        /* ─────────── JSON de TODOS los step logs (sin payload) ─────────── */
        LEFT JOIN (
          SELECT
            l.case_id,
            JSON_ARRAYAGG(
              JSON_OBJECT(
                'id', l.id,
                'case_id', l.case_id,
                'from_state', l.from_state,
                'to_state', l.to_state,
                'from_sub', l.from_sub,
                'to_sub', l.to_sub,
                'type', l.type,
                'user_id', l.user_id,
                'comments', l.comments,
                'created_at', l.created_at
              ) ORDER BY l.created_at
            ) AS step_logs_json
          FROM grupoint_obi_cases.case_step_logs l
          GROUP BY l.case_id
        ) sl ON sl.case_id = c.id;
        SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)
          ->statement('DROP VIEW IF EXISTS `v_cases_details`');
    }
};
