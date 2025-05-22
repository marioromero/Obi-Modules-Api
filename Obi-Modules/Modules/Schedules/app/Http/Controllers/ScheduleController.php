<?php

namespace Modules\Schedules\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Schedules\Models\Schedule;
use App\Http\Controllers\Controller;

class ScheduleController extends BaseApiController
{

    public function index()
    {
        $paginator = Schedule::paginate(15);
        return $this->paginated($paginator, 'Listado de schedules');
    }

    public function show(Schedule $schedule)
    {
        return $this->success($schedule, 'Schedule obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $schedule = Schedule::create($data);

        return $this->success($schedule, 'Schedule creado correctamente', 201);
    }

    public function update(Request $request, Schedule $schedule)
    {
        $data = $request->validate(['name' => 'required|string']);
        $schedule->update($data);

        return $this->success($schedule, 'Schedule actualizado correctamente');
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
        return $this->success(null, 'Schedule eliminado correctamente', 204);
    }
}
