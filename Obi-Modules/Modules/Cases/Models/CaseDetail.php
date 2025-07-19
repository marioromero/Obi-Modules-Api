<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Cases\Models\CaseEntityStepLog;   // 🟢 relación correcta
use App\Models\User;                          // si lo necesitas en otros lugares

class CaseDetail extends Model
{
    /* Vista v_cases_details (conexión cases_db) */
    protected $connection = 'cases_db';
    protected $table      = 'v_cases_details';
    public    $timestamps = false;

    /* Al serializar: incluir step_logs */
    protected $appends = ['step_logs'];

    /* ───────── Relaciones ───────── */

    /** Historial completo de logs del caso */
    public function stepLogs()
    {
        return $this->hasMany(CaseEntityStepLog::class, 'case_id')
                    ->orderBy('created_at');
    }

    /* ───────── Accessor ───────── */

    /** Devuelve todos los logs con nombre de usuario */
    public function getStepLogsAttribute()
    {
        return $this->stepLogs()
                    ->with('user:id,name')          // trae solo id y name
                    ->get()
                    ->map(function ($log) {
                        return [
                            'id'         => $log->id,
                            'from_state' => $log->from_state,
                            'from_sub'   => $log->from_sub,
                            'to_state'   => $log->to_state,
                            'to_sub'     => $log->to_sub,
                            'type'       => $log->type,
                            'user_id'    => $log->user_id,
                            'user_name'  => optional($log->user)->name,
                            'payload'    => $log->payload,
                            'comments'   => $log->comments,
                            'created_at' => $log->created_at,
                        ];
                    });
    }
}
