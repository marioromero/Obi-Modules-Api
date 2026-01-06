<?php

namespace Modules\Mailing\Models;

use Modules\Core\app\Support\Traits\DeletionStrategies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSchedule extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'email_schedules';
    public $timestamps = false;

    protected $fillable = [
        'start_in',
        'ending_at',
        'send_once',
        'failed_or_pendings',
        'is_retry',
        'customer_set_id',
        'email_template_id',
    ];

    // Relación con CustomerSet
    public function customerSet()
    {
        return $this->belongsTo(CustomersSet::class, 'customer_set_id');
    }

    // Relación con EmailTemplate
    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function sends()
    {
        return $this->hasMany(\Modules\Mailing\Models\Send::class, 'email_schedule_id');
    }
}
