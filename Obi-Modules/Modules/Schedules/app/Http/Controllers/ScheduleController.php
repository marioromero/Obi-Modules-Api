<?php

namespace Modules\Schedules\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Schedules\Models\Schedule;
use App\Http\Controllers\Controller;

class ScheduleController extends Controller
{

    public function index()
    {
        $data = Schedule::paginate(15);
        return response()->json($data);
    }

    public function show(Schedule $schedule)
    {
        return response()->json($schedule);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $schedule = Schedule::create($data);
        return response()->json($schedule, 201);
    }

    public function update(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $schedule->update($data);
        return response()->json($schedule);
    }

    public function patch(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $schedule->update($data);
        return response()->json($schedule);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->noContent();
    }
}
