<?php

namespace Modules\Geography\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Geography\Models\Province;
use App\Http\Controllers\Controller;


class ProvinceController extends BaseApiController
{

    public function index()
    {
        $paginator = Province::paginate(15);
        return $this->paginated($paginator, 'Listado de provinces');
    }

    public function show(Province $province)
    {
        return $this->success($province, 'Province obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $province = Province::create($data);

        return $this->success($province, 'Province creado correctamente', 201);
    }

    public function update(Request $request, Province $province)
    {
        $data = $request->validate(['name' => 'required|string']);
        $province->update($data);

        return $this->success($province, 'Province actualizado correctamente');
    }

    public function patch(Request $request, Province $province)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $province->update($data);

        return $this->success($province, 'Province parcialmente actualizado');
    }

    public function destroy(Province $province)
    {
        $province->delete();
        return $this->success(null, 'Province eliminado correctamente', 204);
    }
}

