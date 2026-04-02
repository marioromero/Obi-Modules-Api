<?php

namespace Modules\Geography\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommuneDistance extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'geography_db';
    protected $table = 'commune_distances';
    public $timestamps = false;

    protected $fillable = [
        'origin_id',
        'destination_id',
        'distance_km',
    ];

    // Relación: CommuneDistance pertenece a una comuna de origen
    public function origin()
    {
        return $this->belongsTo(Commune::class, 'origin_id');
    }

    // Relación: CommuneDistance pertenece a una comuna de destino
    public function destination()
    {
        return $this->belongsTo(Commune::class, 'destination_id');
    }
}
