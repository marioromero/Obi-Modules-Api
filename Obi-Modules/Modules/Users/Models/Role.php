<?php

namespace Modules\Users\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'users_db';
    protected $table = 'roles';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    // Relación de Role con User (un Role tiene muchos Users)
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
