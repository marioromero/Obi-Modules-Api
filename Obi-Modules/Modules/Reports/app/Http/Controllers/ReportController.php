<?php

namespace Modules\Reports\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Reports\Models\Report;
use App\Http\Controllers\Controller;

class ReportController extends Controller
{

    public function index()
    {
        $data = Report::paginate(15);
        return response()->json($data);
    }

    public function show(Report $report)
    {
        return response()->json($report);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $report = Report::create($data);
        return response()->json($report, 201);
    }

    public function update(Request $request, Report $report)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $report->update($data);
        return response()->json($report);
    }

    public function patch(Request $request, Report $report)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $report->update($data);
        return response()->json($report);
    }

    public function destroy(Report $report)
    {
        $report->delete();
        return response()->noContent();
    }
}
