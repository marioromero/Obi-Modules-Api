<?php

namespace Modules\Cases\Models;

use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Cases\Database\Factories\StatsFactory;

class Stats extends Model
{
    use DeletionStrategies;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): StatsFactory
    // {
    //     // return StatsFactory::new();
    // }
}
