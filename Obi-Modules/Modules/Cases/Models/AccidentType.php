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
    public $timestamps = true;

    protected $fillable = ['name'];

    // Relación de AccidentType con CaseEntity (un tipo de accidente tiene muchos casos)
    public function cases()
    {
        return $this->hasMany(CaseEntity::class, 'accident_type_id');
    }

    // Excluir registros con softdeleted = 1
    protected static function booted()
    {
        static::addGlobalScope('exclude_softdeleted', function ($query) {
            $query->where('softdeleted', 0);
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutGlobalScope('exclude_softdeleted')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}

