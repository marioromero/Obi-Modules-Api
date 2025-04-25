<?php

namespace Modules\Schedules\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleStatus extends Model
{
    use HasFactory;

    protected $connection = 'schedules_db';
    protected $table = 'schedule_statuses';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de ScheduleStatus con Schedule (un ScheduleStatus tiene muchos Schedules)
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'schedule_state_id');
    }
}
