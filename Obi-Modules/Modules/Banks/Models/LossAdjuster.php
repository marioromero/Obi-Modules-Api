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
        'insurer_id',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de LossAdjuster con Insurer (un LossAdjuster pertenece a un Insurer)
    //Cascade
    public function insurer()
    {
        return $this->belongsTo(Insurer::class, 'insurer_id');
    }
}
