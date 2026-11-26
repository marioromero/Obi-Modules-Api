<?php

namespace Modules\Schedules\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Schedules\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Schedules\app\Http\Requests\StoreScheduleRequest;
use Modules\Schedules\app\Http\Requests\UpdateScheduleRequest;
use Modules\Schedules\Models\ScheduleDetail;
use Modules\Cases\Support\CasesCache;

class ScheduleController extends BaseApiController
{
    protected string $conn = 'schedules_db';

    public function index()
    {
        $schedules = Schedule::all();
        return $this->success($schedules, 'Listado de programaciones', 200);
    }    

    public function show(Schedule $schedule)
    {
        return $this->success($schedule, 'Programación obtenida correctamente');
    }

    public function store(StoreScheduleRequest $request)
    {
        $schedule = Schedule::create($request->validated());

        return $this->success($schedule, 'Programación creada correctamente', 200);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule)
    {
        $schedule->update($request->validated());

        return $this->success($schedule, 'Programación actualizada correctamente');
    }

    public function patch(Request $request, Schedule $schedule)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $schedule->update($data);

        return $this->success($schedule, 'Schedule parcialmente actualizado');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return $this->success(null, 'Programación eliminada correctamente', 204);
    }

    //Metodos con logica de negocio de traro

    //Listar todas las programaciones de un caso (vigente primero)
    public function indexScheduleByCaseId($caseId)
    {
         $schedules = ScheduleDetail::on($this->conn) // <- CAMBIO: ScheduleDetail
         ->where('case_id', $caseId)
         ->orderByDesc('id')
         ->get();

        return $this->success($schedules, 'Listado de programaciones del caso');
    }

    //Crear primera programación o reprogramar (si viene is_reprogramming = true)
    public function save(Request $req, $caseId)
    {
        $data = $req->validate([
            'inspection_date'           => 'nullable|date',
            'inspection_time'           => 'nullable|regex:/^\d{2}:\d{2}$/',
            'liquidator_inspector_info' => 'nullable|string',
            'comments'                  => 'nullable|string',
            'is_reprogramming'          => 'nullable|boolean',
            'consultant_id'             => 'nullable|integer',
            'loss_adjuster_id'          => 'nullable|integer',
            'message_sent'              => 'nullable|boolean',
            'message_confirmed'         => 'nullable|boolean',
            'inspection_failed'         => 'nullable|boolean',
            'inspection_cancelled'      => 'nullable|boolean',
            'user_name'                 => 'nullable|string',
        ]);

        return DB::connection($this->conn)->transaction(function () use ($caseId, $data) {
            // Lock fila vigente para serializar ráfagas (doble-click / submit concurrente).
            $current = Schedule::on($this->conn)
                ->where('case_id', $caseId)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $isReprog = (bool)($data['is_reprogramming'] ?? false);

            // Comentario obligatorio al reprogramar: el front ya lo exige,
            // pero el backend debe ser la barrera final (llamadas directas a API).
            if ($isReprog && trim((string)($data['comments'] ?? '')) === '') {
                // Sin crear fila. Transacción confirma sin writes.
                return $this->error('Debe ingresar el motivo de la reprogramación', 422);
            }

            //Primera programación
            if (!$current) {
                $schedule = Schedule::on($this->conn)->create([
                    'case_id'                   => $caseId,
                    'inspection_date'           => $data['inspection_date'] ?? null,
                    'inspection_time'           => $data['inspection_time'] ?? null,
                    'liquidator_inspector_info' => $data['liquidator_inspector_info'] ?? null,
                    'consultant_id'             => $data['consultant_id'] ?? null,
                    'loss_adjuster_id'          => $data['loss_adjuster_id'] ?? null,
                    'message_sent'              => $data['message_sent'] ?? false,
                    'message_confirmed'         => $data['message_confirmed'] ?? false,
                    'inspection_failed'         => $data['inspection_failed'] ?? false,
                    'inspection_cancelled'      => $data['inspection_cancelled'] ?? false,
                ]);

                $detail = ScheduleDetail::on($this->conn)->find($schedule->id);
                CasesCache::syncOne((int) $caseId);
                return $this->success($detail, 'Programación creada correctamente');
            }

        // Reprogramación
        if ($isReprog) {

            // Dedup: si la vigente ya tiene exactamente los mismos datos de agenda,
            // es un reintento / doble-submit. No crear fila fantasma.
            $sameSlot = $current
                && ($current->inspection_date ?? null) === ($data['inspection_date'] ?? null)
                && ($current->inspection_time ?? null) === ($data['inspection_time'] ?? null)
                && (int)($current->consultant_id ?? 0) === (int)($data['consultant_id'] ?? 0)
                && (int)($current->loss_adjuster_id ?? 0) === (int)($data['loss_adjuster_id'] ?? 0);

            if ($sameSlot) {
                $detail = ScheduleDetail::on($this->conn)->find($current->id);
                return $this->success($detail, 'La programación vigente ya tiene esos datos, no se duplicó');
            }

            // Guardar comentario y/o marcar visita fallida/cancelada SOLO en la programación anterior.
            // El comentario se guarda en `comments` como JSON [{hora, contenido, usuario}]
            // siguiendo el patrón de historial de comentarios de programación. Las filas
            // antiguas con texto plano se convierten a JSON con usuario/fecha "Sin información".
            $newCommentText = trim((string)($data['comments'] ?? ''));
            if ($newCommentText !== '' || isset($data['inspection_failed']) || isset($data['inspection_cancelled'])) {

                $usuario = !empty(trim((string)($data['user_name'] ?? '')))
                    ? trim((string)$data['user_name'])
                    : (auth()->user()?->name ?? 'Sin información');
                $hora = now()->format('d-m-Y H:i');

                $nuevoEntry = [
                    'hora' => $hora,
                    'contenido' => $newCommentText,
                    'usuario' => $usuario,
                ];

                $entradas = [];
                $raw = trim((string)$current->comments);
                if ($raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $entradas = array_values($decoded);
                    } else {
                        // Texto plano de reprogramación antigua: se conserva el contenido
                        // pero sin datos de auditoría (blindado como "Sin información").
                        $entradas[] = [
                            'hora' => 'Sin información',
                            'contenido' => $raw,
                            'usuario' => 'Sin información',
                        ];
                    }
                }
                $entradas[] = $nuevoEntry;

                $current->update([
                    'comments' => json_encode($entradas, JSON_UNESCAPED_UNICODE),
                    // Si viene inspection_failed / inspection_cancelled → úsalo. Si no viene, no modificar.
                    'inspection_failed' => array_key_exists('inspection_failed', $data)
                        ? (bool)$data['inspection_failed']
                        : $current->inspection_failed,
                    'inspection_cancelled' => array_key_exists('inspection_cancelled', $data)
                        ? (bool)$data['inspection_cancelled']
                        : $current->inspection_cancelled,
                ]);
            }

                //Crear nueva programación
                $new = Schedule::on($this->conn)->create([
                    'case_id'                   => $caseId,
                    'inspection_date'           => $data['inspection_date'] ?? null,
                    'inspection_time'           => $data['inspection_time'] ?? null,
                    'liquidator_inspector_info' => $data['liquidator_inspector_info'] ?? null,
                    'comments'                  => null,
                    'consultant_id'             => $data['consultant_id'] ?? null,
                    'loss_adjuster_id'          => $data['loss_adjuster_id'] ?? null,
                    'message_sent'              => $data['message_sent'] ?? false,
                    'message_confirmed'         => $data['message_confirmed'] ?? false,
                    'inspection_failed'         => false,
                    'inspection_cancelled'      => false,
                ]);

                $detail = ScheduleDetail::on($this->conn)->find($new->id);
                CasesCache::syncOne((int) $caseId);
                return $this->success($detail, 'Reprogramación creada correctamente');
            }

                return $this->error('Debe especificar si es una creación o reprogramación', 400);
        });
    }

    /**
     * Guardado atómico usado por la CRM: programación + campos de agenda del caso
     * en una sola transacción MySQL. Si algo falla, no se guarda ninguna de las dos.
     */
    public function saveAtomic(Request $req, $caseId)
    {
        $data = $req->validate([
            'inspection_date'           => 'nullable|date',
            'inspection_time'           => 'nullable|regex:/^\d{2}:\d{2}$/',
            'liquidator_inspector_info' => 'nullable|string',
            'comments'                  => 'nullable|string',
            'is_reprogramming'          => 'required|boolean',
            'consultant_id'             => 'nullable|integer',
            'loss_adjuster_id'          => 'nullable|integer',
            'insurer_id'                => 'nullable|integer',
            'message_sent'              => 'nullable|boolean',
            'message_confirmed'         => 'nullable|boolean',
            'inspection_failed'         => 'nullable|boolean',
            'inspection_cancelled'      => 'nullable|boolean',
            'user_name'                 => 'nullable|string',
            'request_id'                => 'nullable|string|max:64',
        ]);

        $requestId = $data['request_id'] ?? null;
        $caseId = (int) $caseId;

        try {
            $detail = DB::connection($this->conn)->transaction(function () use ($caseId, $data) {
                $caseExists = DB::connection($this->conn)
                    ->table('grupoint_obi_cases.cases')
                    ->where('id', $caseId)
                    ->exists();

                if (! $caseExists) {
                    throw ValidationException::withMessages([
                        'case_id' => 'El caso indicado no existe.',
                    ]);
                }

                $current = Schedule::on($this->conn)
                    ->where('case_id', $caseId)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $isReprog = (bool) ($data['is_reprogramming'] ?? false);

                // Primera programación.
                if (! $current) {
                    $schedule = Schedule::on($this->conn)->create([
                        'case_id'                   => $caseId,
                        'inspection_date'           => $data['inspection_date'] ?? null,
                        'inspection_time'           => $data['inspection_time'] ?? null,
                        'liquidator_inspector_info' => $data['liquidator_inspector_info'] ?? null,
                        'consultant_id'             => $data['consultant_id'] ?? null,
                        'loss_adjuster_id'          => $data['loss_adjuster_id'] ?? null,
                        'message_sent'              => $data['message_sent'] ?? false,
                        'message_confirmed'         => $data['message_confirmed'] ?? false,
                        'inspection_failed'         => $data['inspection_failed'] ?? false,
                        'inspection_cancelled'      => $data['inspection_cancelled'] ?? false,
                    ]);

                    $detail = ScheduleDetail::on($this->conn)->find($schedule->id);
                    $this->syncCaseAgenda($caseId, $detail, $data);

                    return $detail;
                }

                // Actualización de la programación vigente (corrección).
                if (! $isReprog) {
                    if ((bool) $current->inspection_cancelled || (bool) $current->inspection_failed) {
                        throw ValidationException::withMessages([
                            'schedule' => 'La programación vigente está cancelada o fallida; debe reprogramar.',
                        ]);
                    }

                    $update = [];
                    foreach ([
                        'inspection_date',
                        'inspection_time',
                        'liquidator_inspector_info',
                        'consultant_id',
                        'loss_adjuster_id',
                        'message_sent',
                        'message_confirmed',
                        'inspection_failed',
                        'inspection_cancelled',
                    ] as $field) {
                        if (array_key_exists($field, $data)) {
                            $update[$field] = $data[$field];
                        }
                    }

                    if ($update !== []) {
                        $current->update($update);
                    }

                    $detail = ScheduleDetail::on($this->conn)->find($current->id);
                    $this->syncCaseAgenda($caseId, $detail, $data);

                    return $detail;
                }

                // Reprogramación.
                $newCommentText = trim((string) ($data['comments'] ?? ''));
                if ($newCommentText === '') {
                    throw ValidationException::withMessages([
                        'comments' => 'Debe ingresar el motivo de la reprogramación.',
                    ]);
                }

                $sameSlot = ($current->inspection_date ?? null) === ($data['inspection_date'] ?? null)
                    && ($current->inspection_time ?? null) === ($data['inspection_time'] ?? null)
                    && (int) ($current->consultant_id ?? 0) === (int) ($data['consultant_id'] ?? 0)
                    && (int) ($current->loss_adjuster_id ?? 0) === (int) ($data['loss_adjuster_id'] ?? 0);

                if ($sameSlot) {
                    $detail = ScheduleDetail::on($this->conn)->find($current->id);
                    $this->syncCaseAgenda($caseId, $detail, $data);

                    return $detail;
                }

                $usuario = ! empty(trim((string) ($data['user_name'] ?? '')))
                    ? trim((string) $data['user_name'])
                    : (auth()->user()?->name ?? 'Sin información');
                $hora = now()->format('d-m-Y H:i');

                $nuevoEntry = [
                    'hora' => $hora,
                    'contenido' => $newCommentText,
                    'usuario' => $usuario,
                ];

                $entradas = [];
                $raw = trim((string) $current->comments);
                if ($raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $entradas = array_values($decoded);
                    } else {
                        $entradas[] = [
                            'hora' => 'Sin información',
                            'contenido' => $raw,
                            'usuario' => 'Sin información',
                        ];
                    }
                }
                $entradas[] = $nuevoEntry;

                $current->update([
                    'comments' => json_encode($entradas, JSON_UNESCAPED_UNICODE),
                    'inspection_failed' => array_key_exists('inspection_failed', $data)
                        ? (bool) $data['inspection_failed']
                        : $current->inspection_failed,
                    'inspection_cancelled' => array_key_exists('inspection_cancelled', $data)
                        ? (bool) $data['inspection_cancelled']
                        : $current->inspection_cancelled,
                ]);

                $new = Schedule::on($this->conn)->create([
                    'case_id'                   => $caseId,
                    'inspection_date'           => $data['inspection_date'] ?? null,
                    'inspection_time'           => $data['inspection_time'] ?? null,
                    'liquidator_inspector_info' => $data['liquidator_inspector_info'] ?? null,
                    'comments'                  => null,
                    'consultant_id'             => $data['consultant_id'] ?? null,
                    'loss_adjuster_id'          => $data['loss_adjuster_id'] ?? null,
                    'message_sent'              => $data['message_sent'] ?? false,
                    'message_confirmed'         => $data['message_confirmed'] ?? false,
                    'inspection_failed'         => false,
                    'inspection_cancelled'      => false,
                ]);

                $detail = ScheduleDetail::on($this->conn)->find($new->id);
                $this->syncCaseAgenda($caseId, $detail, $data);

                return $detail;
            });
        } catch (\Throwable $e) {
            Log::error('[Schedules] Error en guardado atómico', [
                'case_id' => $caseId,
                'request_id' => $requestId,
                'mode' => ($data['is_reprogramming'] ?? false) ? 'reprogramming' : 'update',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        try {
            CasesCache::syncOne($caseId);
        } catch (\Throwable $e) {
            Log::warning('[Schedules] No se pudo sincronizar cache tras guardado atómico', [
                'case_id' => $caseId,
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success($detail, 'Programación guardada correctamente');
    }

    private function syncCaseAgenda(int $caseId, $detail, array $data): void
    {
        if (! $detail) {
            throw new \RuntimeException('No se pudo leer la programación guardada.');
        }

        $caseUpdate = [
            'inspection_date' => $detail->inspection_date,
            'consultant_id' => $detail->consultant_id,
            'loss_adjuster_id' => $detail->loss_adjuster_id,
            'updated_at' => now(),
        ];

        if (array_key_exists('insurer_id', $data)) {
            $caseUpdate['insurer_id'] = $data['insurer_id'];
        }

        DB::connection($this->conn)
            ->table('grupoint_obi_cases.cases')
            ->where('id', $caseId)
            ->update($caseUpdate);
    }

    //Actualizar programación vigente (sin crear nueva)
    public function updateSchedule(Request $req, $caseId)
    {
        $data = $req->validate([
            'inspection_date'           => 'nullable|date',
            'inspection_time'           => 'nullable|regex:/^\d{2}:\d{2}$/',
            'liquidator_inspector_info' => 'nullable|string',
            'consultant_id'             => 'nullable|integer',
            'loss_adjuster_id'          => 'nullable|integer',

            //  no pisa si no viene
            'message_sent'              => 'sometimes|boolean',
            'message_confirmed'         => 'sometimes|boolean',

            'inspection_failed'         => 'nullable|boolean',
            'inspection_cancelled'      => 'nullable|boolean',
        ]);

        $schedule = Schedule::on($this->conn)
            ->where('case_id', $caseId)
            ->orderByDesc('id')
            ->first();

        if (! $schedule) {
            return $this->error('No existe programación vigente para este caso', 404);
        }

        $schedule->update($data);

        $sent      = array_key_exists('message_sent', $data) ? (bool)$data['message_sent'] : null;
        $confirmed = array_key_exists('message_confirmed', $data) ? (bool)$data['message_confirmed'] : null;

        if ($sent !== null || $confirmed !== null) {
            CasesCache::patchScheduleFlags((int)$caseId, $sent, $confirmed);
        }

        CasesCache::syncOne((int) $caseId);

        $detail = ScheduleDetail::on($this->conn)->find($schedule->id);
        return $this->success($detail, 'Programación actualizada correctamente');
    }
}

