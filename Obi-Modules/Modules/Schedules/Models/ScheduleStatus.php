<?php

namespace Modules\Schedules\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Schedules\Database\Factories\ScheduleStatusFactory;

class ScheduleStatus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): ScheduleStatusFactory
    // {
    //     // return ScheduleStatusFactory::new();
    // }
}
