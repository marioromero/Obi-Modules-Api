<?php

namespace Modules\Geography\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Geography\Models\Region;
use App\Http\Controllers\Controller;

class RegionController extends BaseApiController
{

    public function index()
    {
        $paginator = Region::paginate(15);
        return $this->paginated($paginator, 'Listado de regions');
    }

    public function show(Region $region)
    {
        return $this->success($region, 'Region obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $region = Region::create($data);

        return $this->success($region, 'Region creado correctamente', 201);
    }

    public function update(Request $request, Region $region)
    {
        $data = $request->validate(['name' => 'required|string']);
        $region->update($data);

        return $this->success($region, 'Region actualizado correctamente');
    }

    public function patch(Request $request, Region $region)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $region->update($data);

        return $this->success($region, 'Region parcialmente actualizado');
    }

    public function destroy(Region $region)
    {
        $region->delete();
        return $this->success(null, 'Region eliminado correctamente', 204);
    }
}

