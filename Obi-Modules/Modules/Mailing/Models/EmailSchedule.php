<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Mailing\Database\Factories\EmailScheduleFactory;

class EmailSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): EmailScheduleFactory
    // {
    //     // return EmailScheduleFactory::new();
    // }
}
