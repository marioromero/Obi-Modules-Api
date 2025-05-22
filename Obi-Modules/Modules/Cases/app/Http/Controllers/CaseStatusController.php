<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Cases\Models\CaseStatus;
use App\Http\Controllers\Controller;

class CaseStatusController extends BaseApiController
{
  
    public function index()
    {
        $paginator = CaseStatus::paginate(15);
        return $this->paginated($paginator, 'Listado de case-statuses');
    }

    public function show(CaseStatus $caseStatus)
    {
        return $this->success($caseStatus, 'CaseStatus obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $caseStatus = CaseStatus::create($data);

        return $this->success($caseStatus, 'CaseStatus creado correctamente', 201);
    }

    public function update(Request $request, CaseStatus $caseStatus)
    {
        $data = $request->validate(['name' => 'required|string']);
        $caseStatus->update($data);

        return $this->success($caseStatus, 'CaseStatus actualizado correctamente');
    }

    public function patch(Request $request, CaseStatus $caseStatus)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $caseStatus->update($data);

        return $this->success($caseStatus, 'CaseStatus parcialmente actualizado');
    }

    public function destroy(CaseStatus $caseStatus)
    {
        $caseStatus->delete();
        return $this->success(null, 'CaseStatus eliminado correctamente', 204);
    }
}
