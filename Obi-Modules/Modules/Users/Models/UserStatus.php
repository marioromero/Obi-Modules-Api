<?php

namespace Modules\Users\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'users_db';
    protected $table = 'user_statuses';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    // Relación de UserStatus con User (un UserStatus tiene muchos Users)
    public function users()
    {
        return $this->hasMany(User::class, 'status_id');
    }
}
