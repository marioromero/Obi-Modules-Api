<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cases\States\Traro\Cancelado;
use Modules\Cases\States\Traro\Desistido;
use Modules\Cases\States\Traro\DesistidoSinVisita;
use Modules\Cases\States\Traro\Recaudacion;
use Modules\Core\app\Support\Traits\DeletionStrategies;
use Spatie\ModelStates\HasStates;
use Modules\Cases\States\Core\CaseEntityState;

class CaseEntity extends Model
{
    use HasStates;
    use DeletionStrategies;
    use HasFactory;

    /* ───────── Configuración básica ───────── */
    protected $connection = 'cases_db';
    protected $table      = 'cases';
    public    $timestamps = false;            // usamos created_at manual

    protected $casts = [
        'state' => CaseEntityState::class,
    ];

    /* ───────── Campos rellenables ───────── */
    protected $fillable = [
        // Identificación
        'code', 'priority_id',

        // Fechas, datos de siniestro y si existe convenio
        'created_at', 'agreement_id', 'date_of_loss', 'contestation_date', 'property_type', 'property_address',

        // Montos
        'approved_amount', 'uf_approved', 'amount_owed', 'amount_paid',

        // Flags genéricos
        'is_duplicated', 'description', 'resolution',

        // Sub-estados por paso
        'signature_status', 'denounce_status', 'scheduling_status',
        'visit_status', 'budget_status', 'decision_result', 'payment_status',

        // Estado global del caso
        'overall_status',

        // Relaciones
        'customer_id', 'assigned_user', 'agent_id', 'created_by', 'accident_type_id', 'commune_id',
    ];

    /* ───────── Helper para calcular overall_status ───────── */
    public function calculateOverallStatus(): string
    {
        // Estados terminales (cerrado)
        if ($this->state->isAny(
            Cancelado::class,
            Desistido::class,
            DesistidoSinVisita::class
        )) {
            return 'cerrado';
        }

        // Pago final completado
        if ($this->state->is(Recaudacion::class)
            && $this->payment_status === 'pagado') {
            return 'cerrado';
        }

        // Pendientes de pago o impugnación
        if (in_array($this->payment_status, ['parcialmente_pagado', 'cobranza_online'])
            || $this->decision_result === 'impugnado'
            || in_array($this->visit_status, ['en_proceso'])) {
            return 'con_pendientes';
        }

        return 'abierto';
    }

    /* ───────── Relaciones internas (módulo Cases) ───────── */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'case_id');
    }

    public function previousCase()
    {
        return $this->belongsTo(self::class, 'previous_case_number');
    }

    //Un caso puede tener un solo convenio (nullable).
    public function agreement()
    {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    public function accidentType()
    {
        return $this->belongsTo(AccidentType::class, 'accident_type_id');
    }

    /* ───────── Relaciones externas ───────── */
    public function customer()
    {
        return $this->belongsTo(\Modules\Customers\Models\Customer::class, 'customer_id');
    }

    public function commune()
    {
        return $this->belongsTo(\Modules\Geography\Models\Commune::class, 'commune_id');
    }

    public function agent()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'agent_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'assigned_user');
    }

    public function creator()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'created_by');
    }

    public function schedules()
    {
        return $this->hasMany(\Modules\Schedules\Models\Schedule::class, 'case_id');
    }

    // Relación con Bank
    public function bank()
    {
        return $this->belongsTo(\Modules\Banks\Models\Bank::class, 'bank_id');
    }

    // Relación con Insurer
    public function insurer()
    {
        return $this->belongsTo(\Modules\Banks\Models\Insurer::class, 'insurer_id');
    }

    // Relación con LossAdjuster
    public function lossAdjuster()
    {
        return $this->belongsTo(\Modules\Banks\Models\LossAdjuster::class, 'loss_adjuster_id');
    }

}
