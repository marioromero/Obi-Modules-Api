<?php

namespace Modules\Schedules\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Schedules\Models\ScheduleStatus;
use App\Http\Controllers\Controller;


class ScheduleStatusController extends Controller
{

    public function index()
    {
        $data = ScheduleStatus::paginate(15);
        return response()->json($data);
    }

    public function show(ScheduleStatus $scheduleStatus)
    {
        return response()->json($scheduleStatus);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $scheduleStatus = ScheduleStatus::create($data);
        return response()->json($scheduleStatus, 201);
    }

    public function update(Request $request, ScheduleStatus $scheduleStatus)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $scheduleStatus->update($data);
        return response()->json($scheduleStatus);
    }

    public function patch(Request $request, ScheduleStatus $scheduleStatus)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $scheduleStatus->update($data);
        return response()->json($scheduleStatus);
    }

    public function destroy(ScheduleStatus $scheduleStatus)
    {
        $scheduleStatus->delete();
        return response()->noContent();
    }
}
