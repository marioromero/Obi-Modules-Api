<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Cases\Models\AccidentType;

use App\Http\Controllers\Controller;

class AccidentTypeController extends BaseApiController
{
    
    public function index()
    {
        $paginator = AccidentType::paginate(15);
        return $this->paginated($paginator, 'Listado de accident-types');
    }

    public function show(AccidentType $accidentType)
    {
        return $this->success($accidentType, 'AccidentType obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $accidentType = AccidentType::create($data);

        return $this->success($accidentType, 'AccidentType creado correctamente', 201);
    }

    public function update(Request $request, AccidentType $accidentType)
    {
        $data = $request->validate(['name' => 'required|string']);
        $accidentType->update($data);

        return $this->success($accidentType, 'AccidentType actualizado correctamente');
    }

    public function patch(Request $request, AccidentType $accidentType)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $accidentType->update($data);

        return $this->success($accidentType, 'AccidentType parcialmente actualizado');
    }

    public function destroy(AccidentType $accidentType)
    {
        $accidentType->delete();
        return $this->success(null, 'AccidentType eliminado correctamente', 204);
    }
}

