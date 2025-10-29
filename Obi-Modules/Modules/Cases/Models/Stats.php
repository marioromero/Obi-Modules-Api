<?php

namespace Modules\Cases\Models;

use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stats extends Model
{
    use DeletionStrategies;

    use HasFactory;

    protected $table = 'stats';

    protected $connection = 'cases_db';

    public $timestamps = false;

    protected $fillable = ['name'];
}
