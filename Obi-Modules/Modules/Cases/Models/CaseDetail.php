<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Cases\Models\CaseEntityStepLog;
use Illuminate\Support\Facades\DB;

class CaseDetail extends Model
{
    protected $connection = 'cases_db';
    protected $table      = 'v_cases_details';
    public    $timestamps = false;

    /* Tipos y JSON */
    protected $casts = [
        // ids / números
        'id'               => 'integer',
        'customer_id'      => 'integer',
        'bank_id'          => 'integer',
        'insurer_id'       => 'integer',
        'loss_adjuster_id' => 'integer',
        'accident_type_id' => 'integer',
        'agreement_id'     => 'integer',
        'priority_id'      => 'integer',
        'consultant_id'    => 'integer',
        'assigned_user'    => 'integer',
        'created_by'       => 'integer',
        'agent_id'         => 'integer',

        // booleans típicos
        'is_duplicated'        => 'boolean',
        //comentarios
        'description' => 'array',
        // JSON de la vista
        'step_logs_json'       => 'array',
        'case_flows_json'      => 'array', 
        'case_flow_last_json'  => 'array',
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',


    ];

    /* Ocultar raw JSONs que no quieres exponer tal cual */
    protected $hidden = [
        'step_logs_json',
        'case_flows_json',
        'case_flow_last_json',
        'sent_to_acepta',
    ];

    /* Atributos calculados que se agregan a la serialización */
    protected $appends = [
        'step_logs',
        'case_flow_last',
        'phone',
    ];

    protected $guarded = [];

    /**
     * Payload oficial para cache: las caracteristicas de la view
     */
    public function toCachePayload(): array
    {
        // para recortar columnas aquí.
        return $this->toArray();
    }

    // Accessor: si la view no trae phone, lo busca en customers_db
    public function getPhoneAttribute()
    {
        // si la view algún día trae phone, respeta ese valor
        if (array_key_exists('phone', $this->attributes) && !is_null($this->attributes['phone'])) {
            return $this->attributes['phone'];
        }

        if (!$this->customer_id) return null;

        // lee directo de customers
        return DB::connection('customers_db')
            ->table('customers')            // o 'v_customers_details'
            ->where('id', $this->customer_id)
            ->value('phone');
    }

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
                    'payload'    => $log->payload ?? null,
                    'comments'   => $log->comments,
                    'created_at' => $log->created_at,
                ];
            })->values();
    }

    /* ---------- Accessor: snapshot del último case_flow ---------- */
    public function getCaseFlowLastAttribute()
    {
        // Devuelve el objeto ya decodificado (gracias al cast 'array')
        return $this->case_flow_last_json ?? null;
    }
    // Excluir registros que tengan softdeleted = 1
    protected static function booted()
    {
        static::addGlobalScope('exclude_softdeleted', function ($query) {
            $query->where('softdeleted', 0);
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutGlobalScope('exclude_softdeleted')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
