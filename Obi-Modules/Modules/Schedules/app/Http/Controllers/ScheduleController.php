<?php

namespace Modules\Schedules\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Schedules\Models\Schedule;
use App\Http\Controllers\Controller;
use Modules\Schedules\app\Http\Requests\StoreScheduleRequest;
use Modules\Schedules\app\Http\Requests\UpdateScheduleRequest;

class ScheduleController extends BaseApiController
{

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
}

