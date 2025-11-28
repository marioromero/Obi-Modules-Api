<?php

namespace Modules\Cases\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;
use Modules\Cases\Support\CasesCache;
use Illuminate\Support\Facades\Log;
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

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

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

        static::saved(function (self $accidentType): void {
            try {
                CasesCache::refreshBy('accident_type', (int) $accidentType->id);
            } catch (\Throwable $e) {
                Log::channel('daily')->error('Error refrescando cache de casos desde AccidentType::saved', [
                    'entity_id' => $accidentType->id,
                    'type'      => 'accident_type',
                    'exception' => $e->getMessage(),
                    'line'      => $e->getLine(),
                    'file'      => $e->getFile(),
                ]);
            }
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutGlobalScope('exclude_softdeleted')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}

