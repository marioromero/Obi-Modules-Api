<?php

namespace Modules\Banks\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;
use Modules\Schedules\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Cases\Support\CasesCache;
use Illuminate\Support\Facades\Log;

class LossAdjuster extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'banks_db';
    protected $table = 'loss_adjusters';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'is_visible',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];


    // Una liquidadora puede tener muchos casos
    public function cases()
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'loss_adjuster_id');
    }

    public function schedules()
    {
        return $this->hasMany(\Modules\Schedules\Models\Schedule::class, 'loss_adjuster_id');
    }

    // Excluir registros con sofdeleted = 1
    protected static function booted()
    {
        static::addGlobalScope('exclude_softdeleted', function ($query) {
            $query->where('softdeleted', 0);
        });

        static::saved(function (self $lossAdjuster): void {
            try {
                CasesCache::refreshBy('loss_adjuster', (int) $lossAdjuster->id);
            } catch (\Throwable $e) {
                Log::channel('daily')->error('Error refrescando cache de casos desde LossAdjuster::saved', [
                    'entity_id' => $lossAdjuster->id,
                    'type'      => 'loss_adjuster',
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

