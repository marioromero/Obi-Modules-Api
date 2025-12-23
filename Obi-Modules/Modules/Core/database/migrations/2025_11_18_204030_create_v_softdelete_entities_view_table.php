<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('configurations_db')->unprepared(<<<'SQL'
CREATE OR REPLACE VIEW v_softdelete_entities AS

/* ============================================================
 * 1) CASOS  (softdelete vía UPDATE, último log por caso)
 * ============================================================ */
SELECT
    'case' AS entity_type,
    c.id   AS entity_id,
    c.updated_at AS deleted_at,
    ul.user_id AS deleted_by_user_id,
    u.name AS deleted_by_user_name,
    ul.id  AS log_id
FROM grupoint_obi_cases.cases c

LEFT JOIN (
    SELECT
        MAX(id) AS last_log_id,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(details, '$.after.id')) AS UNSIGNED) AS case_id
    FROM grupoint_obi_users.user_logs
    WHERE model_id = 1          -- case
      AND event_id = 2          -- update
      AND JSON_EXTRACT(details, '$.after.softdeleted') = 1
    GROUP BY case_id
) x
    ON x.case_id = c.id

LEFT JOIN grupoint_obi_users.user_logs ul
    ON ul.id = x.last_log_id

LEFT JOIN grupoint_traro.users u
    ON u.id = ul.user_id

WHERE c.softdeleted = 1

UNION ALL

/* ============================================================
 * 2) Tipo de siniestro
 * ============================================================ */
SELECT
    'accident_type' AS entity_type,
    at.id   AS entity_id,
    at.updated_at AS deleted_at,
    ul.user_id AS deleted_by_user_id,
    u.name AS deleted_by_user_name,
    ul.id  AS log_id
FROM grupoint_obi_cases.accident_types at
LEFT JOIN grupoint_obi_users.user_logs ul
    ON ul.model_id = 8
   AND ul.event_id = 24
   AND (
        CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.before.id')) AS UNSIGNED) = at.id
     OR CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.after.id'))  AS UNSIGNED) = at.id
   )
LEFT JOIN grupoint_traro.users u
    ON u.id = ul.user_id
WHERE at.softdeleted = 1

UNION ALL

/* ============================================================
 * 3) Clientes
 * ============================================================ */
SELECT
    'customer' AS entity_type,
    c.id   AS entity_id,
    c.updated_at AS deleted_at,
    ul.user_id AS deleted_by_user_id,
    u.name AS deleted_by_user_name,
    ul.id  AS log_id
FROM grupoint_obi_customers.customers c
LEFT JOIN grupoint_obi_users.user_logs ul
    ON ul.model_id = 2
   AND ul.event_id = 6
   AND (
        CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.before.id')) AS UNSIGNED) = c.id
     OR CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.after.id'))  AS UNSIGNED) = c.id
   )
LEFT JOIN grupoint_traro.users u
    ON u.id = ul.user_id
WHERE c.softdeleted = 1

UNION ALL

/* ============================================================
 * 4) Bancos
 * ============================================================ */
SELECT
    'bank' AS entity_type,
    b.id   AS entity_id,
    b.updated_at AS deleted_at,
    ul.user_id AS deleted_by_user_id,
    u.name AS deleted_by_user_name,
    ul.id  AS log_id
FROM grupoint_obi_banks.banks b
LEFT JOIN grupoint_obi_users.user_logs ul
    ON ul.model_id = 4
   AND ul.event_id = 12
   AND (
        CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.before.id')) AS UNSIGNED) = b.id
     OR CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.after.id'))  AS UNSIGNED) = b.id
   )
LEFT JOIN grupoint_traro.users u
    ON u.id = ul.user_id
WHERE b.softdeleted = 1

UNION ALL

/* ============================================================
 * 5) Aseguradoras
 * ============================================================ */
SELECT
    'insurer' AS entity_type,
    i.id   AS entity_id,
    i.updated_at AS deleted_at,
    ul.user_id AS deleted_by_user_id,
    u.name AS deleted_by_user_name,
    ul.id  AS log_id
FROM grupoint_obi_banks.insurers i
LEFT JOIN grupoint_obi_users.user_logs ul
    ON ul.model_id = 5
   AND ul.event_id = 15
   AND (
        CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.before.id')) AS UNSIGNED) = i.id
     OR CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.after.id'))  AS UNSIGNED) = i.id
   )
LEFT JOIN grupoint_traro.users u
    ON u.id = ul.user_id
WHERE i.softdeleted = 1

UNION ALL

/* ============================================================
 * 6) Liquidadoras
 * ============================================================ */
SELECT
    'loss_adjuster' AS entity_type,
    l.id   AS entity_id,
    l.updated_at AS deleted_at,
    ul.user_id AS deleted_by_user_id,
    u.name AS deleted_by_user_name,
    ul.id  AS log_id
FROM grupoint_obi_banks.loss_adjusters l
LEFT JOIN grupoint_obi_users.user_logs ul
    ON ul.model_id = 6
   AND ul.event_id = 18
   AND (
        CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.before.id')) AS UNSIGNED) = l.id
     OR CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.after.id'))  AS UNSIGNED) = l.id
   )
LEFT JOIN grupoint_traro.users u
    ON u.id = ul.user_id
WHERE l.softdeleted = 1

UNION ALL

/* ============================================================
 * 7) Usuarios
 * ============================================================ */
SELECT
    'user' AS entity_type,
    u2.id   AS entity_id,
    u2.updated_at AS deleted_at,
    ul.user_id AS deleted_by_user_id,
    u.name AS deleted_by_user_name,
    ul.id  AS log_id
FROM grupoint_traro.users u2
LEFT JOIN grupoint_obi_users.user_logs ul
    ON ul.model_id = 3
   AND ul.event_id = 9
   AND (
        CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.before.id')) AS UNSIGNED) = u2.id
     OR CAST(JSON_UNQUOTE(JSON_EXTRACT(ul.details, '$.after.id'))  AS UNSIGNED) = u2.id
   )
LEFT JOIN grupoint_traro.users u
    ON u.id = ul.user_id
WHERE u2.softdeleted = 1;
SQL);
    }

    public function down(): void
    {
        DB::connection('configurations_db')
            ->unprepared('DROP VIEW IF EXISTS v_softdelete_entities;');
    }
};
