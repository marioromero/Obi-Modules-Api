<?php

namespace Modules\Schedules\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Support\Traits\DeletionStrategies;

class DispatchDetail extends Model
{
    use HasFactory, DeletionStrategies;

    protected $connection = 'schedules_db';
    protected $table = 'dispatch_detail';
    public $timestamps = false;

    protected $fillable = [
        'dispatch_id',
        'movement_type_id',
        'cases',
        'origin',
        'destination',
        'km_traveled',
        'fare_value',
        'comments',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de DispatchDetail con MovementType (un DispatchDetail pertenece a un MovementType)
    public function movementType()
    {
        return $this->belongsTo(MovementType::class, 'movement_type_id', 'id');
    }

    // Relación de DispatchDetail con Dispatch (un DispatchDetail pertenece a un Dispatch)
    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class, 'dispatch_id', 'id');
    }
}
