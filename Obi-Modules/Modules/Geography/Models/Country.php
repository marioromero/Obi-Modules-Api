<?php

namespace Modules\Geography\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'geography_db';
    protected $table = 'countries';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'demonym_male',
        'demonym_female',
    ];

    // Relación de Country con Region (un Country tiene muchas Regions)
    //Cascade
    public function regions()
    {
        return $this->hasMany(Region::class, 'country_id');
    }
}
