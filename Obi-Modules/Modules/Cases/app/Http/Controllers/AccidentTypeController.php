<?php

namespace Modules\Cases\app\Http\Controllers;

use Modules\Core\app\Http\BaseApiController;
use Illuminate\Http\Request;
use Modules\Cases\Models\AccidentType;

class AccidentTypeController extends BaseApiController
{
    public function index()
    {
        $types = AccidentType::all();
        return $this->success($types, 'Listado de tipos de siniestro', 200);
    }

    public function show(AccidentType $accidentType)
    {
        return $this->success($accidentType, 'Tipo de siniestro obtenido correctamente', 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|min:1|max:100']);
        $type = AccidentType::create(['name' => trim($data['name'])]);

        return $this->success($type, 'Tipo de siniestro creado correctamente', 201);
    }

    public function update(Request $request, AccidentType $accidentType)
    {
        $data = $request->validate(['name' => 'required|string']);
        $accidentType->update($data);

        return $this->success($accidentType, 'Tipo de siniestro actualizado correctamente', 200);
    }

    public function patch(Request $request, AccidentType $accidentType)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $accidentType->update($data);

        return $this->success($accidentType, 'Tipo de siniestro parcialmente actualizado', 200);
    }

    public function destroy(AccidentType $accidentType)
    {
        $accidentType->delete();
        return $this->success(null, 'Tipo de siniestro eliminado correctamente', 200);
    }

    public function softDelete(AccidentType $accidentType)
    {
        DB::connection('cases_db')
            ->table($accidentType->getTable())
            ->where('id', $accidentType->id)
            ->update([
                'softdeleted' => DB::raw('1 - softdeleted'),
                'updated_at'  => now(),
            ]);

        return $this->success($accidentType->refresh(), 'Tipo de accidente actualizado (softdeleted toggled).', 200);
    }
}
