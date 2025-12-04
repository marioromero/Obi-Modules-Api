<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'schedules_db';

    public function up(): void
    {
        $schedulesDb = config('database.connections.schedules_db.database');  // grupoint_obi_scheduling
        $casesDb     = config('database.connections.cases_db.database');      // grupoint_obi_cases
        $banksDb     = config('database.connections.banks_db.database');      // grupoint_obi_banks
        $traroDb     = config('database.connections.traro_db.database');      // grupoint_traro
        $customersDb = config('database.connections.customers_db.database');  // grupoint_obi_customers

        // 1) DROP
        DB::connection($this->connection)->statement('DROP VIEW IF EXISTS `v_schedules_details`');

        // 2) CREATE
        DB::connection($this->connection)->statement(<<<SQL
        CREATE OR REPLACE VIEW `v_schedules_details` AS
        SELECT
            s.id,
            s.case_id,
            s.consultant_id,
            s.loss_adjuster_id,
            s.liquidator_inspector_info,
            s.inspection_date,
            s.inspection_time,
            s.comments,
            s.message_sent,
            s.message_confirmed,
            s.inspection_failed,

            /* ───── Nombres resueltos ───── */
            c.customer_id        AS customer_id,    -- ID de cliente
            c.code               AS case_code,      -- Codigo caso
            c.state              AS state,          -- Estado
            cu.name              AS customer_name,  -- Nombre cliente
            la.name              AS loss_adjuster_name,
            u.name               AS consultant_name

        FROM {$schedulesDb}.schedules s
        LEFT JOIN {$casesDb}.cases          c  ON c.id  = s.case_id
        LEFT JOIN {$customersDb}.customers  cu ON cu.id = c.customer_id
        LEFT JOIN {$banksDb}.loss_adjusters la ON la.id = s.loss_adjuster_id
        LEFT JOIN {$traroDb}.users          u  ON u.id  = s.consultant_id;
        SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)
          ->statement('DROP VIEW IF EXISTS `v_schedules_details`');
    }
};
