<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Cases\Models\AccidentType;

use App\Http\Controllers\Controller;

class AccidentTypeController extends BaseApiController
{
    
    public function index()
    {
        $types = AccidentType::all();

        return $this->success($types, 'Listado de tipos de siniestro completo');
    }

    public function show(AccidentType $accidentType)
    {
        return $this->success($accidentType, 'AccidentType obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:60',
        ]);
        $type = AccidentType::create($data);
        return $this->success($type, 'Tipo de siniestro creado correctamente', 201);
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

        return $this->success(null, 'Tipo de siniestro eliminado correctamente', 200);
    }
}

