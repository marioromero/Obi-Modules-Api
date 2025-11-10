<?php

namespace Modules\Banks\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Banks\Models\Insurer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class InsurerController extends BaseApiController
{
    
    public function index()
    {
        $insurers = Insurer::all();
        return $this->success($insurers, 'Listado de aseguradoras');
    }

    public function show(Insurer $insurer)
    {
        return $this->success($insurer, 'Aseguradora obtenida correctamente');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|min:1|max:100']);
        $insurer = Insurer::create(['name' => trim($data['name'])]);

        return $this->success($insurer, 'Aseguradora creada correctamente', 201);
    }

    public function update(Request $request, Insurer $insurer)
    {
        $data = $request->validate(['name' => 'required|string']);
        $insurer->update($data);

        return $this->success($insurer, 'Aseguradora actualizada correctamente');
    }

    public function patch(Request $request, Insurer $insurer)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $insurer->update($data);

        return $this->success($insurer, 'Aseguradora parcialmente actualizada');
    }

    public function destroy(Insurer $insurer)
    {
        $insurer->delete();
        return $this->success(null, 'Aseguradora eliminada correctamente', 200);
    }

    public function softDelete(Insurer $insurer)
    {
        DB::connection('banks_db')
            ->table($insurer->getTable())
            ->where('id', $insurer->id)
            ->update([
                'softdeleted' => DB::raw('1 - softdeleted'),
                'updated_at'  => now(),
            ]);

        return $this->success($insurer->refresh(), 'Aseguradora actualizada (softdeleted toggled).', 200);
    }
}

