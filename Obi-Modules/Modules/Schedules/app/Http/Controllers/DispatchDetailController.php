<?php

namespace Modules\Schedules\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Schedules\Models\Dispatch;
use Modules\Schedules\Models\DispatchDetail;
use Illuminate\Support\Facades\DB;
use Modules\Schedules\app\Http\Requests\StoreDispatchDetailRequest;
use Modules\Schedules\app\Http\Requests\UpdateDispatchDetailRequest;

class DispatchDetailController extends BaseApiController
{
    protected string $conn = 'schedules_db';

    public function index()
    {
        $details = DispatchDetail::all();
        return $this->success($details, 'Listado de detalles de desplazamiento', 200);
    }

    public function show(DispatchDetail $dispatchDetail)
    {
        return $this->success($dispatchDetail, 'Detalle de desplazamiento obtenido correctamente');
    }

    public function store(StoreDispatchDetailRequest $request)
    {
        $detail = DispatchDetail::create($request->validated());

        return $this->success($detail, 'Detalle de desplazamiento creado correctamente', 200);
    }

    public function update(UpdateDispatchDetailRequest $request, DispatchDetail $dispatchDetail)
    {
        $dispatchDetail->update($request->validated());

        return $this->success($dispatchDetail, 'Detalle de desplazamiento actualizado correctamente');
    }

    public function patch(Request $request, DispatchDetail $dispatchDetail)
    {
        $data = $request->validate([
            'movement_type_id' => 'sometimes|integer',
            'cases' => 'sometimes|string',
            'origin' => 'sometimes|integer',
            'destination' => 'sometimes|integer',
            'km_traveled' => 'sometimes|integer',
            'fare_value' => 'sometimes|integer|nullable',
            'comments' => 'sometimes|string|nullable',
        ]);
        $dispatchDetail->update($data);

        return $this->success($dispatchDetail, 'Detalle de desplazamiento parcialmente actualizado');
    }

    public function destroy(DispatchDetail $dispatchDetail)
    {
        // Verificar que el refund_status del dispatch sea false antes de eliminar
        $dispatch = Dispatch::find($dispatchDetail->dispatch_id);
        if ($dispatch && $dispatch->refund_status) {
            return $this->error('No se puede eliminar el desplazamiento porque el desplazamiento tiene estado de reembolso activo', 400);
        }

        $dispatchDetail->delete();
        return $this->success(null, 'Detalle de desplazamiento eliminado correctamente', 204);
    }

    //Metodos con logica de negocio

    //Listar detalles por dispatch_id
    public function indexByDispatchId($dispatchId)
    {
        $details = DispatchDetail::on($this->conn)
            ->where('dispatch_id', $dispatchId)
            ->orderByDesc('id')
            ->get();

        return $this->success($details, 'Listado de detalles del desplazamiento');
    }

    //Obtener detalle con su tipo de movimiento
    public function showWithMovementType(DispatchDetail $dispatchDetail)
    {
        $dispatchDetail->load('movementType');

        return $this->success($dispatchDetail, 'Detalle de desplazamiento con tipo de movimiento obtenido correctamente');
    }
}
