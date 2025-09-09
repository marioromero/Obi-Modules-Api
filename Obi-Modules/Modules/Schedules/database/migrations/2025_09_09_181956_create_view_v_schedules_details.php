<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'schedules_db';

    public function up(): void
    {
        $schedulesDb = config('database.connections.schedules_db.database');
        $casesDb     = config('database.connections.cases_db.database');
        $banksDb     = config('database.connections.banks_db.database');
        $traroDb     = config('database.connections.traro_db.database');

        // 1) DROP
        DB::connection($this->connection)->statement('DROP VIEW IF EXISTS `v_schedules_details`');

        // 2) CREATE
        DB::connection($this->connection)->statement(<<<SQL
        CREATE OR REPLACE VIEW `v_schedules_details` AS
        SELECT
            s.id,
            s.case_id,
            s.inspection_date,
            s.inspection_time,
            s.accident_number,
            s.comments,
            s.insurer_id,
            s.loss_adjuster_id,
            s.consultant_id,

            /* ───── Nombres resueltos ───── */
            c.code  AS case_code,
            i.name  AS insurer_name,
            la.name AS loss_adjuster_name,
            u.name  AS consultant_name

        FROM {$schedulesDb}.schedules s
        LEFT JOIN {$casesDb}.cases           c  ON c.id  = s.case_id
        LEFT JOIN {$banksDb}.insurers        i  ON i.id  = s.insurer_id
        LEFT JOIN {$banksDb}.loss_adjusters  la ON la.id = s.loss_adjuster_id
        LEFT JOIN {$traroDb}.users           u  ON u.id  = s.consultant_id;
        SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)
          ->statement('DROP VIEW IF EXISTS `v_schedules_details`');
    }
};
    