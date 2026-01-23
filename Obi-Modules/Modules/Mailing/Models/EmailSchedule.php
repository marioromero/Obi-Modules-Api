<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Support\Traits\DeletionStrategies;
use DateTimeInterface;
use DateTimeZone;

class EmailSchedule extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'email_schedules';

    protected $fillable = [
        'start_in',
        'ending_at',
        'send_once',
        'sends_ok',
        'failed_or_pendings',
        'is_retry',
        'status',
        'only_business_days',
        'user_id',
        'customer_set_id',
        'email_template_id',
    ];

    protected $casts = [
        'start_in'           => 'datetime',
        'ending_at'          => 'datetime',
        'send_once'          => 'integer',
        'sends_ok'           => 'integer',
        'failed_or_pendings' => 'integer',
        'is_retry'           => 'boolean',
        'only_business_days' => 'boolean',
        'user_id'            => 'integer',
        'customer_set_id'    => 'integer',
        'email_template_id'  => 'integer',
        'status'             => 'string',
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date
            ->setTimezone(new DateTimeZone('America/Santiago'))
            ->format('Y-m-d H:i:s');
    }


    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\TraroUser::class,'user_id');
    }

    public function customerSet()
    {
        return $this->belongsTo( \Modules\Mailing\Models\CustomersSet::class,'customer_set_id');
    }

    public function emailTemplate()
    {
        return $this->belongsTo(\Modules\Mailing\Models\EmailTemplate::class,'email_template_id');
    }

    public function sends()
    {
        return $this->hasMany(\Modules\Mailing\Models\Send::class,'email_schedule_id');
    }
}
