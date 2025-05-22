<?php

namespace Modules\Geography\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Geography\Models\Commune;
use App\Http\Controllers\Controller;


class CommuneController extends BaseApiController
{

    public function index()
    {
        $paginator = Commune::paginate(15);
        return $this->paginated($paginator, 'Listado de communes');
    }

    public function show(Commune $commune)
    {
        return $this->success($commune, 'Commune obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $commune = Commune::create($data);

        return $this->success($commune, 'Commune creado correctamente', 201);
    }

    public function update(Request $request, Commune $commune)
    {
        $data = $request->validate(['name' => 'required|string']);
        $commune->update($data);

        return $this->success($commune, 'Commune actualizado correctamente');
    }

    public function patch(Request $request, Commune $commune)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $commune->update($data);

        return $this->success($commune, 'Commune parcialmente actualizado');
    }

    public function destroy(Commune $commune)
    {
        $commune->delete();
        return $this->success(null, 'Commune eliminado correctamente', 204);
    }
}
