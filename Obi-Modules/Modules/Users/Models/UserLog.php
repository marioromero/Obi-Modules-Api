<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    use HasFactory;

    protected $connection = 'users_db';
    protected $table = 'user_logs';
    public $timestamps = false;

    protected $fillable = [
        'timestamp',
        'datails',
        'user_id',
        'event_id',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de UserLog con User (un UserLog pertenece a un User)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación de UserLog con Event (un UserLog pertenece a un Event)
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
