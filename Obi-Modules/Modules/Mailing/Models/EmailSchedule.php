<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSchedule extends Model
{
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'email_schedules';
    public $timestamps = false;

    protected $fillable = [
        'start_in',
        'send_once',
        'send_frecuency_days',
        'customer_set',
        'email_template',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de EmailSchedule con CustomersSet (un EmailSchedule pertenece a un CustomersSet)
    //Cascade
    public function customersSet()
    {
        return $this->belongsTo(CustomersSet::class, 'customer_set');
    }

    // Relación de EmailSchedule con EmailTemplate (un EmailSchedule pertenece a un EmailTemplate)
    //No Action
    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template');
    }
}
