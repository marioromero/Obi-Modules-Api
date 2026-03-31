<?php

namespace Modules\Schedules\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Support\Traits\DeletionStrategies;

class MovementType extends Model
{
    use HasFactory, DeletionStrategies;

    protected $connection = 'schedules_db';
    protected $table = 'movement_types';
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de MovementType con DispatchDetail (un MovementType tiene muchos DispatchDetail)
    public function dispatchDetails()
    {
        return $this->hasMany(DispatchDetail::class, 'movement_type_id', 'id');
    }
}
