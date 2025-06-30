<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Support\Traits\DeletionStrategies;

class CaseEntity extends Model
{
    use DeletionStrategies;
    use HasFactory;

    /* ───────── Configuración básica ───────── */
    protected $connection = 'cases_db';
    protected $table      = 'cases';
    public    $timestamps = false;            // usamos created_at manual


    /* ───────── Campos rellenables ───────── */
        protected $fillable = [
        // Identificación
        'code', 'priority_id', 'accident_number', 'bank_service_number',

        // Fechas, datos de siniestro y si existe convenio
        'created_at', 'agreement_id', 'budget_sending_date', 'document_signing_date',
        'date_of_loss', 'contestation_date', 'settlement_report_date', 'probable_payment_date', 'online_collection_date',
        'property_type', 'property_address', 'inspection_date', 'complaint_date', 'collection_date',

        // Montos
        'approved_amount', 'uf_approved',
        'amount_owed', 'amount_paid', 'advisory_amount',

        // Flags genéricos
        'is_duplicated', 'description', 'resolution',

        // Relaciones
        'customer_id', 'assigned_user', 'agent_id', 'created_by',
        'accident_type_id', 'commune_id',
        'bank_id', 'insurer_id', 'loss_adjuster_id', 'consultant_id',
    ];

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

    public function consultant()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'consultant_id');
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
