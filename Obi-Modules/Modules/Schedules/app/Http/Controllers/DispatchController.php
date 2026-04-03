<?php

namespace Modules\Schedules\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Schedules\Models\Dispatch;
use Illuminate\Support\Facades\DB;
use Modules\Schedules\app\Http\Requests\StoreDispatchRequest;
use Modules\Schedules\app\Http\Requests\UpdateDispatchRequest;

class DispatchController extends BaseApiController
{
    protected string $conn = 'schedules_db';

    public function index()
    {
        $dispatches = Dispatch::all();
        return $this->success($dispatches, 'Listado de salidas', 200);
    }

    public function show(Dispatch $dispatch)
    {
        return $this->success($dispatch, 'Despacho obtenido correctamente');
    }

    public function store(StoreDispatchRequest $request)
    {
        $dispatch = Dispatch::create($request->validated());

        return $this->success($dispatch, 'Despacho creado correctamente', 200);
    }

    public function update(UpdateDispatchRequest $request, Dispatch $dispatch)
    {
        $dispatch->update($request->validated());

        return $this->success($dispatch, 'Despacho actualizado correctamente');
    }

    public function patch(Request $request, Dispatch $dispatch)
    {
        $data = $request->validate(['date' => 'sometimes|date']);
        $dispatch->update($data);

        return $this->success($dispatch, 'Despacho parcialmente actualizado');
    }

    public function destroy(Dispatch $dispatch)
    {
        // Verificar que refund_status sea false antes de eliminar
        if ($dispatch->refund_status) {
            return $this->error('No se puede eliminar el despacho porque tiene estado de reembolso activo', 400);
        }

        $dispatch->delete();
        return $this->success(null, 'Despacho eliminado correctamente', 204);
    }

    //Metodos con logica de negocio

    //Listar despacho por fecha
    public function indexByDate($date)
    {
        $dispatches = Dispatch::on($this->conn)
            ->where('date', $date)
            ->orderByDesc('id')
            ->get();

        return $this->success($dispatches, 'Listado de salidas por fecha');
    }

    //Listar salidas por asesor
    public function indexByAssistantId($assistantId)
    {
        $dispatches = Dispatch::on($this->conn)
            ->where('assistant_id', $assistantId)
            ->orderByDesc('id')
            ->get();

        return $this->success($dispatches, 'Listado de salidas del asesor');
    }

    //Obtener despacho con sus detalles
    public function showWithDetails(Dispatch $dispatch)
    {
        $dispatch->load('details');

        return $this->success($dispatch, 'Despacho con detalles obtenido correctamente');
    }

    //Listar salidas por rango de fechas (inclusive)
    public function indexByDateRange($startDate, $endDate)
    {
        $dispatches = Dispatch::on($this->conn)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return $this->success($dispatches, 'Listado de salidas por rango de fechas');
    }
}
