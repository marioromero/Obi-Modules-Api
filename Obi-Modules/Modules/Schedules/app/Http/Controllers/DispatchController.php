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
        return $this->success($dispatches, 'Listado de despachos', 200);
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

        return $this->success($dispatches, 'Listado de despachos por fecha');
    }

    //Obtener despacho con sus detalles
    public function showWithDetails(Dispatch $dispatch)
    {
        $dispatch->load('details');

        return $this->success($dispatch, 'Despacho con detalles obtenido correctamente');
    }
}
