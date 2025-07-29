<?php

namespace Modules\Banks\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insurer extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'banks_db';
    protected $table = 'insurers';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_visible',
    ];

    // Una aseguradora puede tener muchos casos
    public function cases()
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'insurer_id');
    }
}

