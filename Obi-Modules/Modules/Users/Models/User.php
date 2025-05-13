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
        return $this->belongsTo(\Modules\Geography\Models\Commune::class, 'commune_id');
    }

    // 4️⃣  Casos según rol
    public function agentCases()
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'agent_id');
    }
    public function consultantCases()
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'consulant_id');
    }
    public function assignedCases()
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'assigned_user');
    }
    public function createdCases()
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'created_by');
    }

    // 5️⃣  Comentarios
    public function comments()
    {
        return $this->hasMany(\Modules\Cases\Models\Comment::class, 'user_id');
    }

    // 7️⃣  Clientes
    public function customers()
    {
        return $this->hasMany(\Modules\Customers\Models\Customer::class, 'user_id');
    }

    // 9️⃣  Mailing (conjuntos y plantillas)
    public function customersSets()
    {
        return $this->hasMany(\Modules\Mailing\Models\CustomersSet::class, 'user_id');
    }
    public function emailTemplates()
    {
        return $this->hasMany(\Modules\Mailing\Models\EmailTemplate::class, 'user_id');
    }

    // 🔟 + 1️⃣4️⃣  Agendas
    public function schedulingSchedules()
    {
        return $this->hasMany(\Modules\Schedules\Models\Schedule::class, 'scheduling_user');
    }
    public function scheduledSchedules()
    {
        return $this->hasMany(\Modules\Schedules\Models\Schedule::class, 'scheduled_user');
    }

    // 1️⃣3️⃣  Reportes
    public function reports()
    {
        return $this->hasMany(\Modules\Reports\Models\Report::class, 'user_id');
    }


}
