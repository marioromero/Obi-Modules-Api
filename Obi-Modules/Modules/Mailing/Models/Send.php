<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Support\Traits\DeletionStrategies;

class Send extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'mailing_db';
    protected $table = 'sends';
    public $timestamps = false;

    protected $fillable = [
        'sent_at',
        'status',
        'email_schedule_id',
        'customer_name',
        'email',
        'customer_detail_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function emailSchedule()
    {
        return $this->belongsTo(\Modules\Mailing\Models\EmailSchedule::class, 'email_schedule_id');
    }

    public function customerDetail()
    {
        return $this->belongsTo(\Modules\Mailing\Models\CustomerDetail::class, 'customer_detail_id');
    }
}
