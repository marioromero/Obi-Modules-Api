<?php

namespace Modules\Schedules\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'schedules_db';
    protected $table = 'schedules';
    public $timestamps = false;

    protected $fillable = [
        'shedule_date',
        'scheduling_user',
        'case_id',
        'scheduled_user',
        'schedule_state_id',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de Schedule con ScheduleStatus (un Schedule pertenece a un ScheduleStatus)
    //No Action
    public function scheduleStatus()
    {
        return $this->belongsTo(ScheduleStatus::class, 'schedule_state_id');
    }

    /** RELACIONES EXTERNAS **/

    // Relación de Schedule con User (Users módulo) - scheduling_user
    //Set Null
    public function schedulingUser()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'scheduling_user');
    }

    // Relación de Schedule con User (Users módulo) - scheduled_user
    public function scheduledUser()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'scheduled_user');
    }

    // Relación de Schedule con CaseEntity (Cases módulo) - case_id
    //Cascade
    public function case()
    {
        return $this->belongsTo(\Modules\Cases\Models\CaseEntity::class, 'case_id');
    }
}

