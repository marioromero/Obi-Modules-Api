<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'mailing_db';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<SQL
CREATE OR REPLACE VIEW v_customers_sets_list AS
SELECT
    cs.id,
    cs.name,
    cs.created_at,
    cs.user_id,
    u.name AS created_by_name,
    COUNT(DISTINCT cd.id) AS recipients_count,
    MAX(s.sent_at) AS last_used_at
FROM grupoint_obi_mailing.customers_sets cs
LEFT JOIN grupoint_obi_mailing.customer_detail cd
    ON cd.customer_set_id = cs.id
LEFT JOIN grupoint_obi_mailing.email_schedules es
    ON es.customer_set_id = cs.id
LEFT JOIN grupoint_obi_mailing.sends s
    ON s.email_schedule_id = es.id
LEFT JOIN grupoint_traro.users u
    ON u.id = cs.user_id
GROUP BY
    cs.id,
    cs.name,
    cs.created_at,
    cs.user_id,
    u.name;
SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(
            'DROP VIEW IF EXISTS v_customers_sets_list'
        );
    }
};
