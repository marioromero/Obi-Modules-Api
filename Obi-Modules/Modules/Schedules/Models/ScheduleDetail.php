<?php

namespace Modules\Schedules\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleDetail extends Model
{
    protected $connection = 'schedules_db';
    protected $table = 'v_schedules_details';
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'case_id',
        'consultant_id',
        'consultant_name',
        'loss_adjuster_id',
        'loss_adjuster_name',
        'liquidator_inspector_info',
        'inspection_date',
        'inspection_time',
        'comments',
        'message_sent',
        'message_confirmed',
    ];
}
