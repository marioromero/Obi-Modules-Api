<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'mailing_db';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<SQL
CREATE OR REPLACE VIEW grupoint_obi_mailing.v_email_schedules_list AS
SELECT
    es.id AS schedule_id,
    cs.id AS customer_set_id,
    cs.name AS customer_set_name,
    es.start_in,
    es.ending_at,
    CONCAT(es.sends_ok, '/', IFNULL(t.total_recipients, 0)) AS status_sends,
    es.is_retry,
    es.user_id AS created_by_user_id,
    u.name AS created_by_user_name,
    es.status
FROM grupoint_obi_mailing.email_schedules es
JOIN grupoint_obi_mailing.customers_sets cs
    ON cs.id = es.customer_set_id
LEFT JOIN (
    SELECT
        customer_set_id,
        COUNT(*) AS total_recipients
    FROM grupoint_obi_mailing.customer_detail
    GROUP BY customer_set_id
) t
    ON t.customer_set_id = es.customer_set_id
LEFT JOIN grupoint_traro.users u
    ON u.id = es.user_id;
SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(
            'DROP VIEW IF EXISTS grupoint_obi_mailing.v_email_schedules_list;'
        );
    }
};
