<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Geography\Models\Commune;

class User extends Model
{
    use HasFactory;

    protected $connection = 'users_db';
    protected $table = 'users';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'lastname',
        'dni',
        'username',
        'password',
        'email',
        'address',
        'phone',
        'gender',
        'status_id',
        'role_id',
        'commune_id',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de User con Role (un User pertenece a un Role)
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Relación de User con UserStatus (un User pertenece a un UserStatus)
    public function status()
    {
        return $this->belongsTo(UserStatus::class, 'status_id');
    }

    // Relación de User con UserLogs (un User tiene muchos UserLogs)
    public function logs()
    {
        return $this->hasMany(UserLog::class, 'user_id');
    }

    /** RELACIONES EXTERNAS **/

    // Relación de User con Commune (un User pertenece a un Commune de otro módulo Geography)
    public function commune()
    {
        return $this->belongsTo(Commune::class, 'commune_id');
    }
}
