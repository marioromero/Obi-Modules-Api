<?php

namespace Modules\Banks\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;
use Modules\Schedules\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cases\Support\CasesCache;
use Illuminate\Support\Facades\Log;

class Insurer extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'banks_db';
    protected $table = 'insurers';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'is_visible',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];


    // Una aseguradora puede tener muchos casos
    public function cases()
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'insurer_id');
    }

    public function schedules()
    {
        return $this->hasMany(\Modules\Schedules\Models\Schedule::class, 'insurer_id');
    }

    // Excluir registros con sofdeleted = 1
    protected static function booted()
    {
        static::addGlobalScope('exclude_softdeleted', function ($query) {
            $query->where('softdeleted', 0);
        });

        static::saved(function (self $insurer): void {
            try {
                CasesCache::refreshBy('insurer', (int) $insurer->id);
            } catch (\Throwable $e) {
                Log::channel('daily')->error('Error refrescando cache de casos desde Insurer::saved', [
                    'entity_id' => $insurer->id,
                    'type'      => 'insurer',
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

