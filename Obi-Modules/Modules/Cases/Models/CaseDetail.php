<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Cases\Models\CaseEntityStepLog;

class CaseDetail extends Model
{
    protected $connection = 'cases_db';
    protected $table      = 'v_cases_details';
    public    $timestamps = false;

    /* Tipos y JSON */
    protected $casts = [
        // ids / números (ajusta según necesites)
        'id' => 'integer',
        'customer_id' => 'integer',
        'bank_id' => 'integer',
        'insurer_id' => 'integer',
        'loss_adjuster_id' => 'integer',
        'accident_type_id' => 'integer',
        'agreement_id' => 'integer',
        'priority_id' => 'integer',
        'consultant_id' => 'integer',
        'assigned_user' => 'integer',
        'created_by' => 'integer',
        'agent_id' => 'integer',

        // booleans típicos
        'is_duplicated' => 'boolean',

        // JSON de la vista
        'step_logs_json'  => 'array',
        'case_flows_json' => 'array',
    ];

    /* Oculta crudos y snapshots de flow (para no duplicar con case_flows_json) */
    protected $hidden = [
        'step_logs_json',
        'fecha_documentos_listos',
        'contrato_enviado_a_acepta',
        'fecha_firma_contrato',
        'mandato_enviado_a_acepta',
        'fecha_firma_mandato',
        'numero_notificaciones_documentos_enviados',
        'numero_notificaciones_documento_pendiente',
        'notificacion_documento_firmado',
        'active_notifications',
    ];

    /* Agrega 'step_logs' calculado al serializar el modelo */
    protected $appends = ['step_logs'];

    /* ---------- Relaciones ---------- */
    public function stepLogs()
    {
        return $this->hasMany(CaseEntityStepLog::class, 'case_id')
                    ->orderBy('created_at');
    }

    /* ---------- Accessor: step_logs con user_name (y payload si existe) ---------- */
    public function getStepLogsAttribute()
    {
        return $this->stepLogs()
            ->with('user:id,name')
            ->get()
            ->map(function ($log) {
                return [
                    'id'         => (int) $log->id,
                    'from_state' => $log->from_state,
                    'from_sub'   => $log->from_sub,
                    'to_state'   => $log->to_state,
                    'to_sub'     => $log->to_sub,
                    'type'       => $log->type,
                    'user_id'    => $log->user_id ? (int) $log->user_id : null,
                    'user_name'  => optional($log->user)->name,
                    'payload'    => $log->payload ?? null,  // si tu tabla lo tiene
                    'comments'   => $log->comments,
                    'created_at' => $log->created_at,
                ];
            })->values();
    }
}
