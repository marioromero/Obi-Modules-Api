<?php

namespace Modules\Geography\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    use HasFactory;

    protected $connection = 'geography_db';
    protected $table = 'communes';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'province_id',
    ];

    // Relación de Commune con Province (una Commune pertenece a una Province)
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }
}
