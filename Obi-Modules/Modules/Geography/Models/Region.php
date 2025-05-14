<?php

namespace Modules\Geography\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $connection = 'geography_db';
    protected $table = 'regions';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'country_id',
        'version_id',
    ];

    // Relación de Region con Country (una Region pertenece a un Country)
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    // Relación de Region con Province (una Region tiene muchas Provinces)
    //Cascade
    public function provinces()
    {
        return $this->hasMany(Province::class, 'region_id');
    }

    //Relación: Una Region pertenece a una VersionPA
    public function versionPA()
    {
        return $this->belongsTo(VersionPA::class, 'version_id');
    }
}
