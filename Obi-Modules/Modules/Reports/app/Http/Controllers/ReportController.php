<?php

namespace Modules\Reports\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Reports\Models\Report;
use Modules\Core\App\Http\BaseApiController;

class ReportController extends BaseApiController
{
    public function index()
    {
        $paginator = Report::paginate(15);
        return $this->paginated($paginator, 'Listado de reports');
    }

    public function show(Report $report)
    {
        return $this->success($report, 'Report obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $report = Report::create($data);

        return $this->success($report, 'Report creado correctamente', 201);
    }

    public function update(Request $request, Report $report)
    {
        $data = $request->validate(['name' => 'required|string']);
        $report->update($data);

        return $this->success($report, 'Report actualizado correctamente');
    }

    public function patch(Request $request, Report $report)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $report->update($data);

        return $this->success($report, 'Report parcialmente actualizado');
    }

    public function destroy(Report $report)
    {
        $report->delete();
        return $this->success(null, 'Report eliminado correctamente', 204);
    }
}
