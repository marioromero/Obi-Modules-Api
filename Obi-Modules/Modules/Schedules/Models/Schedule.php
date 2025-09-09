<?php

namespace Modules\Schedules\Models;

use Modules\Core\app\Support\Traits\DeletionStrategies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cases\Models\CaseEntity;
use Modules\Banks\Models\Insurer;
use Modules\Banks\Models\LossAdjuster;
use Modules\Users\Models\TraroUser;

class Schedule extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'schedules_db';
    protected $table = 'schedules';
    public $timestamps = false;

    protected $fillable = [
        'case_id',
        'inspection_date',
        'inspection_time',
        'accident_number',
        'comments',
        'insurer_id',
        'loss_adjuster_id',
        'consultant_id',
    ];

    //Relaciones

    // Caso asociado (cases_db)
    public function case()
    {
        return $this->belongsTo(CaseEntity::class, 'case_id', 'id');
    }

    // Aseguradora (banks_db)
    public function insurer()
    {
        return $this->belongsTo(Insurer::class, 'insurer_id', 'id');
    }

    // Liquidadora (banks_db)
    public function lossAdjuster()
    {
        return $this->belongsTo(LossAdjuster::class, 'loss_adjuster_id', 'id');
    }

    // Asesor (traro_db.users)
    public function consultant()
    {
        return $this->belongsTo(TraroUser::class, 'consultant_id', 'id');
    }
}
