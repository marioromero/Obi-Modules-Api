<?php

namespace Modules\Schedules\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Support\Traits\DeletionStrategies;
use Modules\Cases\Models\CaseEntity;

class Schedule extends Model
{
    use HasFactory, DeletionStrategies;

    protected $connection = 'schedules_db';
    protected $table = 'schedules';
    public $timestamps = false;

    protected $fillable = [
        'case_id',
        'consultant_id',
        'loss_adjuster_id',
        'liquidator_inspector_info',
        'inspection_date',
        'inspection_time',
        'comments',
        'message_sent',
        'message_confirmed',
        'inspection_failed',
    ];

    public function case()
    {
        return $this->belongsTo(CaseEntity::class, 'case_id', 'id');
    }

    public function consultant()
    {
        return $this->belongsTo(\Modules\Users\Models\TraroUser::class, 'consultant_id', 'id');
    }

    public function lossAdjuster()
    {
        return $this->belongsTo(\Modules\Banks\Models\LossAdjuster::class, 'loss_adjuster_id', 'id');
    }
}
