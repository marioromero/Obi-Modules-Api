<?php

namespace Modules\Banks\Models;
use Modules\Core\app\Support\Traits\DeletionStrategies;
use Modules\Cases\Support\CasesCache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use DeletionStrategies;
    use HasFactory;

    protected $connection = 'banks_db';
    protected $table = 'banks';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'is_visible',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];


    // Un banco puede tener muchos casos
    public function cases()
    {
        return $this->hasMany(\Modules\Cases\Models\CaseEntity::class, 'bank_id');
    }

    // Excluir registros con sofdeleted = 1
    protected static function booted()
    {
        static::addGlobalScope('exclude_softdeleted', function ($query) {
            $query->where('softdeleted', 0);
        });

        static::saved(function (self $bank): void {
            try {
                CasesCache::refreshBy('bank', (int) $bank->id);
            } catch (\Throwable $e) {
                Log::channel('daily')->error('Error refrescando cache de casos desde Bank::saved', [
                    'entity_id' => $bank->id,
                    'type'      => 'bank',
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

