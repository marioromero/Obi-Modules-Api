<?php

namespace Modules\Schedules\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Support\Traits\DeletionStrategies;

class Dispatch extends Model
{
    use HasFactory, DeletionStrategies;

    protected $connection = 'schedules_db';
    protected $table = 'dispatches';
    public $timestamps = false;

    protected $fillable = [
        'date',
        'assistant_id',
    ];

    /** RELACIONES INTERNAS **/

    // Relación de Dispatch con DispatchDetail (un Dispatch tiene muchos DispatchDetail)
    public function details()
    {
        return $this->hasMany(DispatchDetail::class, 'dispatch_id', 'id');
    }
}
