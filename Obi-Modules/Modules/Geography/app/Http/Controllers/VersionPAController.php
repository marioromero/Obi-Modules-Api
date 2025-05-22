<?php

namespace Modules\Geography\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Geography\Models\VersionPA;
use App\Http\Controllers\Controller;


class VersionPAController extends BaseApiController
{

    public function index()
    {
        $paginator = VersionPA::paginate(15);
        return $this->paginated($paginator, 'Listado de version-p-as');
    }

    public function show(VersionPA $versionPA)
    {
        return $this->success($versionPA, 'VersionPA obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $versionPA = VersionPA::create($data);

        return $this->success($versionPA, 'VersionPA creado correctamente', 201);
    }

    public function update(Request $request, VersionPA $versionPA)
    {
        $data = $request->validate(['name' => 'required|string']);
        $versionPA->update($data);

        return $this->success($versionPA, 'VersionPA actualizado correctamente');
    }

    public function patch(Request $request, VersionPA $versionPA)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $versionPA->update($data);

        return $this->success($versionPA, 'VersionPA parcialmente actualizado');
    }

    public function destroy(VersionPA $versionPA)
    {
        $versionPA->delete();
        return $this->success(null, 'VersionPA eliminado correctamente', 204);
    }
}
