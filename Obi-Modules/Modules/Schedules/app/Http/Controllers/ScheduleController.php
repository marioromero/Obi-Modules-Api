<?php

namespace Modules\Schedules\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Schedules\Models\Schedule;
use Illuminate\Support\Facades\DB;
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
        ]);

        return DB::connection($this->conn)->transaction(function () use ($caseId, $data) {
            $current = Schedule::on($this->conn)
                ->where('case_id', $caseId)
                ->orderByDesc('id')
                ->first();

            $isReprog = (bool)($data['is_reprogramming'] ?? false);

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
                ]);

                $detail = ScheduleDetail::on($this->conn)->find($schedule->id);
                CasesCache::syncOne((int) $caseId);
                return $this->success($detail, 'Programación creada correctamente');
            }

        // Reprogramación
        if ($isReprog) {

            // Guardar comentario y/o marcar visita fallida SOLO en la programación anterior
            if (!empty($data['comments']) || isset($data['inspection_failed'])) {

                $previousComments = trim((string)$current->comments);
                $newComment = $previousComments === ''
                    ? ($data['comments'] ?? '')
                    : ($previousComments . "\n" . ($data['comments'] ?? ''));

                $current->update([
                    'comments' => $newComment,
                    // Si viene inspection_failed → úsalo. Si no viene, no modificar.
                    'inspection_failed' => array_key_exists('inspection_failed', $data)
                        ? (bool)$data['inspection_failed']
                        : $current->inspection_failed,
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
                ]);

                $detail = ScheduleDetail::on($this->conn)->find($new->id);
                CasesCache::syncOne((int) $caseId);
                return $this->success($detail, 'Reprogramación creada correctamente');
            }

                return $this->error('Debe especificar si es una creación o reprogramación', 400);
        });
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

