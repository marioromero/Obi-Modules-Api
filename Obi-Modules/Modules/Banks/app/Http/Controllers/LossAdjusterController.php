<?php

namespace Modules\Banks\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Banks\Models\LossAdjuster;
use App\Http\Controllers\Controller;


class LossAdjusterController extends BaseApiController
{
    
    public function index()
    {
        $paginator = LossAdjuster::paginate(15);
        return $this->paginated($paginator, 'Listado de loss-adjusters');
    }

    public function show(LossAdjuster $lossAdjuster)
    {
        return $this->success($lossAdjuster, 'LossAdjuster obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $lossAdjuster = LossAdjuster::create($data);

        return $this->success($lossAdjuster, 'LossAdjuster creado correctamente', 201);
    }

    public function update(Request $request, LossAdjuster $lossAdjuster)
    {
        $data = $request->validate(['name' => 'required|string']);
        $lossAdjuster->update($data);

        return $this->success($lossAdjuster, 'LossAdjuster actualizado correctamente');
    }

    public function patch(Request $request, LossAdjuster $lossAdjuster)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $lossAdjuster->update($data);

        return $this->success($lossAdjuster, 'LossAdjuster parcialmente actualizado');
    }

    public function destroy(LossAdjuster $lossAdjuster)
    {
        $lossAdjuster->delete();
        return $this->success(null, 'LossAdjuster eliminado correctamente', 204);
    }
}
