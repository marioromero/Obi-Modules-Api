<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;                // conexión traro_db
use Modules\Cases\Models\CaseEntity;

class CaseEntityStepLog extends Model
{
    protected $connection = 'cases_db';
    protected $table      = 'case_step_logs';
    public    $timestamps = false;

    protected $fillable = [
        'case_id',
        'from_state', 'to_state',
        'from_sub',   'to_sub',
        'type',
        'user_id',
        'payload',
        'comments',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    /* ───────── Relaciones ───────── */

    public function case()
    {
        return $this->belongsTo(CaseEntity::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')
                    ->select('id', 'name');
    }
}
