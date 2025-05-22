<?php

namespace Modules\Cases\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cases\Models\CaseEntity;

class AccidentType extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'cases_db';
    protected $table = 'accident_types';
    public $timestamps = false;

    protected $fillable = ['name'];

    // Relación de AccidentType con CaseEntity (un tipo de accidente tiene muchos casos)
    public function cases()
    {
        return $this->hasMany(CaseEntity::class, 'accident_type_id');
    }
}

