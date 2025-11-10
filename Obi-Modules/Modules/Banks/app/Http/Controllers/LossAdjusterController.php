<?php

namespace Modules\Banks\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Banks\Models\LossAdjuster;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class LossAdjusterController extends BaseApiController
{
    
    public function index()
    {
        $lossAdjusters = LossAdjuster::all();
        return $this->success($lossAdjusters, 'Listado de liquidadoras');
    }

    public function show(LossAdjuster $lossAdjuster)
    {
        return $this->success($lossAdjuster, 'Liquidadora obtenido correctamente');
    }

     public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|min:1|max:100']);
        $lossAdjuster = LossAdjuster::create(['name' => trim($data['name'])]);

        return $this->success($lossAdjuster, 'Liquidadora creada correctamente', 201);
    }

    public function update(Request $request, LossAdjuster $lossAdjuster)
    {
        $data = $request->validate(['name' => 'required|string']);
        $lossAdjuster->update($data);

        return $this->success($lossAdjuster, 'Liquidadora actualizada correctamente');
    }

    public function patch(Request $request, LossAdjuster $lossAdjuster)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $lossAdjuster->update($data);

        return $this->success($lossAdjuster, 'Liquidadora parcialmente actualizada');
    }

    public function destroy(LossAdjuster $lossAdjuster)
    {
        $lossAdjuster->delete();
        return $this->success(null, 'Liquidadora eliminada correctamente', 200);
    }

    public function softDelete(LossAdjuster $lossAdjuster)
    {
        DB::connection('banks_db')
            ->table($lossAdjuster->getTable())
            ->where('id', $lossAdjuster->id)
            ->update([
                'softdeleted' => DB::raw('1 - softdeleted'),
                'updated_at'  => now(),
            ]);

        return $this->success($lossAdjuster->refresh(), 'Liquidadora actualizada (softdeleted toggled).', 200);
    }
}

