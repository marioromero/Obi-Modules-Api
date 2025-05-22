<?php

namespace Modules\Users\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'users_db';
    protected $table = 'events';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    // Relación de Event con UserLog (un Event tiene muchos UserLogs)
    public function userLogs()
    {
        return $this->hasMany(UserLog::class, 'event_id');
    }
}

