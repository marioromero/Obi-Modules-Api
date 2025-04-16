<?php

namespace Modules\Appointments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Appointments\Database\Factories\AppointmentStatusFactory;

class AppointmentStatus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): AppointmentStatusFactory
    // {
    //     // return AppointmentStatusFactory::new();
    // }
}
