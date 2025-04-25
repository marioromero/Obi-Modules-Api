<?php

namespace Modules\Cases\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseEntity extends Model
{
    use HasFactory;

    protected $connection = 'cases_db';
    protected $table = 'cases';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'priority',
        'claim_number',
        'created_at',
        'contestation_date',
        'date_of_loss',
        'document_signing_date',
        'inspection_date',
        'settlement_report_date',
        'reporting_date',
        'property_type',
        'claim_tracking_number',
        'previous_case_number',
        'quote_submission_date',
        'agreement',
        'is_duplicated',
        'approved_amount',
        'uf_approved',
        'consultancy_amount',
        'amount_owed',
        'ammount_paid',
        'estimated_payment_day',
        'collection_date',
        'online_collection_date',
        'payment_status',
        'entry_date',
        'description',
        'rejection_reason',
        'resolution',
        'agent_id',
        'consulant_id',
        'commune_id',
        'customer_id',
        'case_status_id',
        'accident_type_id',
        'assigned_user',
        'created_by',
    ];

    /** RELACIONES INTERNAS (dentro de Cases) **/

    // Relación de CaseEntity con CaseStatus (un CaseEntity pertenece a un CaseStatus)
    public function caseStatus()
    {
        return $this->belongsTo(CaseStatus::class, 'case_status_id');
    }

    // Relación de CaseEntity con AccidentType (un CaseEntity pertenece a un AccidentType)
    public function accidentType()
    {
        return $this->belongsTo(AccidentType::class, 'accident_type_id');
    }

    // Relación de CaseEntity con Priority (un CaseEntity pertenece a una Priority)
    public function priorityRelation()
    {
        return $this->belongsTo(Priority::class, 'priority');
    }

    // Relación de CaseEntity con sus propios Comments (un CaseEntity tiene muchos Comments)
    public function comments()
    {
        return $this->hasMany(Comment::class, 'case_id');
    }

    // Relación de CaseEntity consigo mismo para previous case
    public function previousCase()
    {
        return $this->belongsTo(CaseEntity::class, 'previous_case_number');
    }

    /** RELACIONES EXTERNAS (a otros módulos) **/

    // Relación de CaseEntity con Geography::Commune
    public function commune()
    {
        return $this->belongsTo(\Modules\Geography\Models\Commune::class, 'commune_id');
    }

    // Relación de CaseEntity con Customers::Customer
    public function customer()
    {
        return $this->belongsTo(\Modules\Customers\Models\Customer::class, 'customer_id');
    }

    // Relación de CaseEntity con Users::User como agente
    public function agent()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'agent_id');
    }

    // Relación de CaseEntity con Users::User como consultor
    public function consultant()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'consulant_id');
    }

    // Relación de CaseEntity con Users::User como usuario asignado
    public function assignedUser()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'assigned_user');
    }

    // Relación de CaseEntity con Users::User como creador
    public function creator()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'created_by');
    }
}
