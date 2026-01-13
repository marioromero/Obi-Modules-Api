<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'mailing_db';

    public function up(): void
    {
        DB::connection($this->connection)->unprepared(<<<SQL
CREATE OR REPLACE VIEW v_customers_mailing AS
SELECT
    c.id AS customer_id,
    c.name,
    c.lastname,
    c.email,
    c.tags,

    cm.name AS commune_name,
    p.name  AS province_name,
    r.name  AS region_name,

    COALESCE(cc.cases_count, 0) AS cases_count

FROM grupoint_obi_customers.customers c

LEFT JOIN grupoint_obi_geography.communes cm
    ON cm.id = c.commune_id

LEFT JOIN grupoint_obi_geography.provinces p
    ON p.id = cm.province_id

LEFT JOIN grupoint_obi_geography.regions r
    ON r.id = p.region_id

LEFT JOIN (
    SELECT customer_id, COUNT(*) AS cases_count
    FROM grupoint_obi_cases.cases
    GROUP BY customer_id
) cc
    ON cc.customer_id = c.id;
SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->unprepared(
            'DROP VIEW IF EXISTS v_customers_mailing'
        );
    }
};
