<?php

namespace Modules\Cases\Models;
use Illuminate\Support\Facades\DB;
use Spatie\ModelStates\HasStates;
use Modules\Cases\States\Core\CaseEntityState;
use Modules\Cases\Models\CaseEntityStepLog;
use Modules\Schedules\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\app\Support\Traits\DeletionStrategies;

class CaseEntity extends Model
{
    protected $casts = [
            'state' => CaseEntityState::class,
            // fechas paso 6  (formato ISO string <-> Carbon)
            'probable_payment_date'  => 'date:Y-m-d',
            'collection_date'        => 'date:Y-m-d',
            'online_collection_date' => 'date:Y-m-d',

            // comentarios
            'description' => 'array',

            // montos enteros
            'amount_paid'  => 'integer',
            'amount_owed'  => 'integer',

            // montos existentes
            'approved_amount' => 'integer',
            'advisory_amount' => 'integer',
            'uf_approved'     => 'float',
    ];

    use HasStates;

    use DeletionStrategies;
    use HasFactory;

    /* ───────── Configuración básica ───────── */
    protected $connection = 'cases_db';
    protected $table      = 'cases';
    public    $timestamps = true;            // usamos created_at manual


    /* ───────── Campos rellenables ───────── */
        protected $fillable = [
        // Identificación
        'code', 'priority_id', 'sent_to_acepta', 'accident_number', 'bank_service_number',

        // Fechas, datos de siniestro y si existe convenio
        'created_at', 'agreement_id', 'budget_sending_date', 'document_signing_date',
        'date_of_loss', 'contestation_date', 'settlement_report_date', 'probable_payment_date', 'online_collection_date',
        'property_type', 'property_address', 'inspection_date', 'complaint_date', 'collection_date', 'comments_programming',

        // Montos
        'approved_amount', 'uf_approved',
        'amount_owed', 'amount_paid', 'advisory_amount', 'amount_owed_including_vat',

        // Flags genéricos
        'is_duplicated', 'description', 'resolution', 'payment_status',

        // Relaciones
        'customer_id', 'assigned_user', 'created_by',
        'accident_type_id', 'commune_id',
        'bank_id', 'insurer_id', 'loss_adjuster_id', 'consultant_id',
    ];

    /* ───────── Relaciones internas (módulo Cases) ───────── */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'case_id');
    }

    public function previousCase()
    {
        return $this->belongsTo(self::class, 'previous_case_number');
    }

    //Un caso puede tener un solo convenio (nullable).
    public function agreement()
    {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    public function accidentType()
    {
        return $this->belongsTo(AccidentType::class, 'accident_type_id');
    }

    /* ───────── Relaciones externas ───────── */
    public function customer()
    {
        return $this->belongsTo(\Modules\Customers\Models\Customer::class, 'customer_id');
    }

    public function commune()
    {
        return $this->belongsTo(\Modules\Geography\Models\Commune::class, 'commune_id');
    }

    public function agent()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'agent_id');
    }

    public function consultant()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'consultant_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'assigned_user');
    }

    public function creator()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class, 'created_by');
    }

    public function schedules()
    {
        return $this->hasMany(\Modules\Schedules\Models\Schedule::class, 'case_id');
    }

    // Relación con Bank
    public function bank()
    {
        return $this->belongsTo(\Modules\Banks\Models\Bank::class, 'bank_id');
    }

    // Relación con Insurer
    public function insurer()
    {
        return $this->belongsTo(\Modules\Banks\Models\Insurer::class, 'insurer_id');
    }

    // Relación con LossAdjuster
    public function lossAdjuster()
    {
        return $this->belongsTo(\Modules\Banks\Models\LossAdjuster::class, 'loss_adjuster_id');
    }

    public function stepLogs()
    {
        return $this->hasMany(CaseEntityStepLog::class, 'case_id');
    }

/**
 * Transiciona un sub-estado correspondiente al estado global actual.
 *
 * @param string $newValue Nuevo valor para la columna de sub-estado.
 * @param string|null $comments Comentarios opcionales para registrar en el log
 * @return $this El objeto del modelo actualizado
 * @throws \InvalidArgumentException
 * @throws \RuntimeException
 */

/**
 * Transiciona a un nuevo estado
 *
 * @param string $stateClass Clase del estado destino
 * @param int|null $userId ID del usuario que realiza la acción
 * @param string|null $comments Comentarios opcionales
 * @return $this
 */
public function transitionTo(string $stateClass, ?int $userId = null, ?string $comments = null): self
{
    // Crear objeto con datos adicionales para el evento
    $transitionProps = [
        'user_id' => $userId,
        'comments' => $comments
    ];

    // Pasar datos al evento mediante el segundo parámetro
    $this->state->transitionTo($stateClass, ['transitionProps' => $transitionProps]);

    return $this->refresh();
}


public function transitionSubstate(string $newValue, ?int $userId = 0, ?string $comments = null): self
{
    // 1) Cargar config y fallback
    $cfg = config('Modules.Cases.CaseEntity_states');
    if (! is_array($cfg)
        || ! isset($cfg['sub_states'], $cfg['auto_transitions'], $cfg['overall_status'])
    ) {
        $path = module_path('Cases', 'Config/CaseEntity_states.php');
        if (! file_exists($path)) {
            throw new \RuntimeException("No se encontró archivo de config: {$path}");
        }
        $cfg = require $path;
    }

    $subStates = $cfg['sub_states'];
    $autoTrans = $cfg['auto_transitions'];
    $overall   = $cfg['overall_status'];
    // namespace desde config, o fallback al inyectado
    $namespace = $cfg['namespace'] ?? 'Traro';

    // Obtener el estado global actual automáticamente
    $currentState = class_basename($this->state);
    $key = $currentState;

    // 2) Validaciones
    if (! isset($subStates[$key])) {
        throw new \InvalidArgumentException("Estado actual '$key' no tiene sub-estados definidos");
    }
    $info = $subStates[$key];
    if (! in_array($newValue, $info['values'], true)) {
        throw new \InvalidArgumentException("Valor '$newValue' no válido para sub-estado de {$key} (columna: {$info['column']})");
    }

    // Refrescar modelo para asegurar que tenemos el valor previo real en BD
    $this->refresh();

    // 3) Transacción atómica
    DB::transaction(function() use (
        $key, $newValue, $info, $subStates, $comments, $userId,
        $autoTrans, $overall, $namespace, $currentState
    ) {
        // a) Capturar sub-estado anterior
        $col      = $info['column'];
        $oldValue = $this->{$col};

        // b) Actualizar columna de sub-estado
        $this->{$col} = $newValue;
        $this->saveQuietly();

        // c) Log de sub-estado
        CaseEntityStepLog::create([
            'case_id'   => $this->id,
            'from_state'    => $currentState,
            'to_state'      => $currentState,
            'from_sub'      => $oldValue,
            'to_sub'        => $newValue,
            'type'          => 'sub_state',
            'user_id'       => $userId ?? 0,
            'payload'       => json_encode([$col => $newValue]),
            'comments'      => $comments,
        ]);

        // d) Ajustar overall_status
        $pending = $overall['triggers']['pending']['sub_states'] ?? [];
        $closed  = $overall['triggers']['closed']['sub_states'] ?? [];
        if (in_array($newValue, $pending, true)) {
            $this->{$overall['column']} = 'con pendientes';
            $this->saveQuietly();
        } elseif (in_array($newValue, $closed, true)) {
            $this->{$overall['column']} = 'cerrado';
            $this->saveQuietly();
        } else {
            // Si no está en ningún trigger, restaurar al estado default
            $this->{$overall['column']} = $overall['default'];
            $this->saveQuietly();
        }

        // e) Auto-transición global si es final
        if ($newValue === $info['final'] && isset($autoTrans[$key])) {
            $nextKey     = $autoTrans[$key];
            $nextInfo    = $subStates[$nextKey] ?? [];
            $nextDefault = $nextInfo['default'] ?? null;

            // overall a pendientes
            $this->{$overall['column']} = 'con pendientes';
            $this->saveQuietly();

            $class = 'Modules\Cases\States\Traro\\' . $nextKey;
            $this->state->transitionTo($class);
        }
    });

    // Refrescar el modelo para devolver la versión actualizada
    $this->refresh();
    return $this;
}
/**
 * Realiza una transición de estado global con comentarios opcionales
 *
 * @param string $stateClass La clase de estado destino
 * @param string|null $comments Comentarios opcionales para el log
 * @return $this
 */
public function transitionToWithComments(string $stateClass, ?string $comments = null, ?int $userId): self
{
    // Realizar la transición normal
    $this->state->transitionTo($stateClass);

    // Si hay comentarios, actualizar el último log
    if ($comments !== null) {
        $lastLog = $this->stepLogs()
            ->where('type', 'state')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLog) {
            $lastLog->update(['comments' => $comments, 'user_id' => $userId]);
        }
    }
    return $this->refresh();
}
     /**
     * Genera el código TR<n> justo antes del INSERT.
     */
   protected static function booted(): void
    {
        // excluye los casos con softdeleted = 1
        static::addGlobalScope('exclude_softdeleted', function ($query) {
            $query->where('softdeleted', 0);
        });

        // generar código TRXXXX incremental
        static::creating(function (self $case): void {

            if ($case->code) {
                return; // ya viene seteado
            }

            DB::connection('cases_db')->transaction(function () use ($case) {

                $max = DB::connection('cases_db')  // conexión explícita
                    ->table('cases')
                    ->where('code', 'like', 'TR%')
                    ->lockForUpdate()
                    ->max(DB::raw('CAST(SUBSTRING(code,3) AS UNSIGNED)'));

                $next       = ($max ?? 0) + 1;
                $case->code = 'TR' . $next;
            });
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::withoutGlobalScope('exclude_softdeleted')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->firstOrFail();
    }
}
