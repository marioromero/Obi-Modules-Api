<?php

namespace Modules\Mailing\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mailing\Models\EmailSchedule;
use App\Http\Controllers\Controller;

class EmailScheduleController extends Controller
{

    public function index()
    {
        $data = EmailSchedule::paginate(15);
        return response()->json($data);
    }

    public function show(EmailSchedule $emailSchedule)
    {
        return response()->json($emailSchedule);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $emailSchedule = EmailSchedule::create($data);
        return response()->json($emailSchedule, 201);
    }

    public function update(Request $request, EmailSchedule $emailSchedule)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $emailSchedule->update($data);
        return response()->json($emailSchedule);
    }

    public function patch(Request $request, EmailSchedule $emailSchedule)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $emailSchedule->update($data);
        return response()->json($emailSchedule);
    }

    public function destroy(EmailSchedule $emailSchedule)
    {
        $emailSchedule->delete();
        return response()->noContent();
    }
}
