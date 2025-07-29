<?php

namespace Modules\Banks\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LossAdjuster extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'banks_db';
    protected $table = 'loss_adjusters';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_visible',
    ];

    // Una liquidadora puede tener muchos casos
    public function cases()
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'loss_adjuster_id');
    }
}

