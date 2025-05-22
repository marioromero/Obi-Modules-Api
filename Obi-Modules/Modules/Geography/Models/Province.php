<?php

namespace Modules\Geography\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'geography_db';
    protected $table = 'provinces';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'region_id',
    ];

    // Relación de Province con Region (una Province pertenece a un Region)
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    // Relación de Province con Commune (una Province tiene muchas Communes)
    //Cascade
    public function communes()
    {
        return $this->hasMany(Commune::class, 'province_id');
    }
}

