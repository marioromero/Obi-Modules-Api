<?php

namespace Modules\Geography\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VersionPA extends Model
{
    use HasFactory;

    protected $connection = 'geography_db';

    protected $table = 'version_p_a';

    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'activo',
    ];

    //Relación: Una VersionPA tiene muchas Region
    public function regions()
    {
        return $this->hasMany(Region::class, 'version_id');
    }
}
