<?php

namespace Modules\Mailing\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'email_templates';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'user_id',
        'content',
        'department_id',
    ];

    /** RELACIONES EXTERNAS **/

    // Relación de EmailTemplate con User (Users módulo)
    //Set Null
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'user_id');
    }
    public function emailSchedules()   // 1️⃣1️⃣  EmailSchedule → EmailTemplate
    {
        return $this->hasMany(EmailSchedule::class, 'email_template');
    }
    //Set Null
    public function department()       // 1️⃣2️⃣  Departamento ↔ Plantilla
    {
        return $this->belongsTo(\Modules\Mailing\Models\Department::class, 'department_id');
    }
}
