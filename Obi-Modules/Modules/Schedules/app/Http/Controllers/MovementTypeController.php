<?php

namespace Modules\Schedules\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Schedules\Models\MovementType;
use Illuminate\Support\Facades\DB;
use Modules\Schedules\app\Http\Requests\StoreMovementTypeRequest;
use Modules\Schedules\app\Http\Requests\UpdateMovementTypeRequest;

class MovementTypeController extends BaseApiController
{
    protected string $conn = 'schedules_db';

    public function index()
    {
        $movementTypes = MovementType::all();
        return $this->success($movementTypes, 'Listado de tipos de movimiento', 200);
    }

    public function show(MovementType $movementType)
    {
        return $this->success($movementType, 'Tipo de movimiento obtenido correctamente');
    }

    public function store(StoreMovementTypeRequest $request)
    {
        $movementType = MovementType::create($request->validated());

        return $this->success($movementType, 'Tipo de movimiento creado correctamente', 200);
    }

    public function update(UpdateMovementTypeRequest $request, MovementType $movementType)
    {
        $movementType->update($request->validated());

        return $this->success($movementType, 'Tipo de movimiento actualizado correctamente');
    }

    public function patch(Request $request, MovementType $movementType)
    {
        $data = $request->validate(['name' => 'sometimes|string|max:100']);
        $movementType->update($data);

        return $this->success($movementType, 'Tipo de movimiento parcialmente actualizado');
    }

    public function destroy(MovementType $movementType)
    {
        $movementType->delete();
        return $this->success(null, 'Tipo de movimiento eliminado correctamente', 204);
    }
}
