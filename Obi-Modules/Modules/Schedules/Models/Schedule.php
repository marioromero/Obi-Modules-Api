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
        'liquidator_inspector_info',
        'inspection_date',
        'inspection_time',
        'comments',
    ];

    public function case()
    {
        return $this->belongsTo(CaseEntity::class, 'case_id', 'id');
    }
}
