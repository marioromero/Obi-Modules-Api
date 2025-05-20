<?php

namespace Modules\Cases\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cases\Models\CaseEntity; // 👈 Corrección importante

class CaseStatus extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'cases_db';
    protected $table = 'case_statuses';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    // Relación de CaseStatus con Case (un CaseStatus tiene muchos Cases)
    public function cases()
    {
        return $this->hasMany(CaseEntity::class, 'case_status_id');
    }
}
