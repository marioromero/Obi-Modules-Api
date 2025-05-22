<?php

namespace Modules\Schedules\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Schedules\Models\ScheduleStatus;
use App\Http\Controllers\Controller;


class ScheduleStatusController extends BaseApiController
{

    public function index()
    {
        $paginator = ScheduleStatus::paginate(15);
        return $this->paginated($paginator, 'Listado de schedule-statuses');
    }

    public function show(ScheduleStatus $scheduleStatus)
    {
        return $this->success($scheduleStatus, 'ScheduleStatus obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $scheduleStatus = ScheduleStatus::create($data);

        return $this->success($scheduleStatus, 'ScheduleStatus creado correctamente', 201);
    }

    public function update(Request $request, ScheduleStatus $scheduleStatus)
    {
        $data = $request->validate(['name' => 'required|string']);
        $scheduleStatus->update($data);

        return $this->success($scheduleStatus, 'ScheduleStatus actualizado correctamente');
    }

    public function patch(Request $request, ScheduleStatus $scheduleStatus)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $scheduleStatus->update($data);

        return $this->success($scheduleStatus, 'ScheduleStatus parcialmente actualizado');
    }

    public function destroy(ScheduleStatus $scheduleStatus)
    {
        $scheduleStatus->delete();
        return $this->success(null, 'ScheduleStatus eliminado correctamente', 204);
    }
}

