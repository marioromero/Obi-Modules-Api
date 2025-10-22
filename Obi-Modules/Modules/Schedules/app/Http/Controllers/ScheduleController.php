<?php

namespace Modules\Schedules\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Schedules\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Modules\Schedules\app\Http\Requests\StoreScheduleRequest;
use Modules\Schedules\app\Http\Requests\UpdateScheduleRequest;
use Modules\Schedules\Models\ScheduleDetail;

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
            'comments_programming'      => 'nullable|string',
            'is_reprogramming'          => 'nullable|boolean',
            'consultant_id'             => 'nullable|integer',
            'loss_adjuster_id'          => 'nullable|integer',
            'message_sent'              => 'nullable|boolean',
            'message_confirmed'         => 'nullable|boolean',
        ]);

        return DB::connection($this->conn)->transaction(function () use ($caseId, $data) {
            $current = Schedule::on($this->conn)
                ->where('case_id', $caseId)
                ->orderByDesc('id')
                ->first();

            $isReprog = (bool)($data['is_reprogramming'] ?? false);

            $globalCommentsProgramming = $data['comments_programming'] ?? ($current->comments_programming ?? null);

            if (array_key_exists('comments_programming', $data)) {
                Schedule::on($this->conn)
                    ->where('case_id', $caseId)
                    ->update(['comments_programming' => $globalCommentsProgramming]);
            }

            //Primera programación
            if (!$current) {
                $schedule = Schedule::on($this->conn)->create([
                    'case_id'                   => $caseId,
                    'inspection_date'           => $data['inspection_date'] ?? null,
                    'inspection_time'           => $data['inspection_time'] ?? null,
                    'liquidator_inspector_info' => $data['liquidator_inspector_info'] ?? null,
                    'comments_programming'      => $globalCommentsProgramming,
                    'consultant_id'             => $data['consultant_id'] ?? null,
                    'loss_adjuster_id'          => $data['loss_adjuster_id'] ?? null,
                    'message_sent'              => $data['message_sent'] ?? false,
                    'message_confirmed'         => $data['message_confirmed'] ?? false,
                ]);

                $detail = ScheduleDetail::on($this->conn)->find($schedule->id);
                return $this->success($detail, 'Programación creada correctamente');
            }

            //Reprogramación
            if ($isReprog) {
                // Agregar comentario solo a la programación anterior
                if (!empty($data['comments'])) {
                    $previousComments = trim((string)$current->comments);
                    $newComment = $previousComments === ''
                        ? $data['comments']
                        : $previousComments . "\n" . $data['comments'];

                    $current->update(['comments' => $newComment]);
                }

                //Crear nueva programación
                $new = Schedule::on($this->conn)->create([
                    'case_id'                   => $caseId,
                    'inspection_date'           => $data['inspection_date'] ?? null,
                    'inspection_time'           => $data['inspection_time'] ?? null,
                    'liquidator_inspector_info' => $data['liquidator_inspector_info'] ?? null,
                    'comments'                  => null,
                    'comments_programming'      => $globalCommentsProgramming,
                    'consultant_id'             => $data['consultant_id'] ?? null,
                    'loss_adjuster_id'          => $data['loss_adjuster_id'] ?? null,
                    'message_sent'              => $data['message_sent'] ?? false,
                    'message_confirmed'         => $data['message_confirmed'] ?? false,
                ]);

                $detail = ScheduleDetail::on($this->conn)->find($new->id);
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
            'comments_programming'      => 'nullable|string',
            'consultant_id'             => 'nullable|integer',
            'loss_adjuster_id'          => 'nullable|integer',
            'message_sent'              => 'nullable|boolean',
            'message_confirmed'         => 'nullable|boolean',
        ]);

        $schedule = Schedule::on($this->conn)
            ->where('case_id', $caseId)
            ->orderByDesc('id')
            ->first();

        if (!$schedule) {
            return $this->error('No existe programación vigente para este caso', 404);
        }

         if (array_key_exists('comments_programming', $data)) {
             $value = $data['comments_programming']; // puede ser null o string
             Schedule::on($this->conn)
                 ->where('case_id', $caseId)
                 ->update(['comments_programming' => $value]);
         }

        $schedule->update($data);

        $detail = ScheduleDetail::on($this->conn)->find($schedule->id);
        return $this->success($detail, 'Programación actualizada correctamente');
    }
}

