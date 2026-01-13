<?php

namespace Modules\Users\Models;
use Modules\Schedules\Models\Schedule;
use Illuminate\Foundation\Auth\User as Authenticatable;

class TraroUser extends Authenticatable
{
    // Fuerza a usar la conexión traro_db
    protected $connection = 'traro_db';

    // La tabla que contiene a los usuarios en Traro
    protected $table = 'users';

    // Ajusta los campos según tu esquema real
    protected $fillable = ['id', 'name', 'username', 'password', 'email', 'gender', 'status_id', 'role_id'];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    //Relaciones
    public function schedules()
    {
        return $this->hasMany(\Modules\Schedules\Models\Schedule::class, 'consultant_id');
    }

    public function emailSchedules()
    {
        return $this->hasMany(
            \Modules\Mailing\Models\EmailSchedule::class,
            'user_id'
        );
    }
}
