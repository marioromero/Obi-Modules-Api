<?php

namespace Modules\Banks\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Banks\Models\Insurer;
use App\Http\Controllers\Controller;

class InsurerController extends BaseApiController
{
    
    public function index()
    {
        $paginator = Insurer::paginate(15);
        return $this->paginated($paginator, 'Listado de insurers');
    }

    public function show(Insurer $insurer)
    {
        return $this->success($insurer, 'Insurer obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $insurer = Insurer::create($data);

        return $this->success($insurer, 'Insurer creado correctamente', 201);
    }

    public function update(Request $request, Insurer $insurer)
    {
        $data = $request->validate(['name' => 'required|string']);
        $insurer->update($data);

        return $this->success($insurer, 'Insurer actualizado correctamente');
    }

    public function patch(Request $request, Insurer $insurer)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $insurer->update($data);

        return $this->success($insurer, 'Insurer parcialmente actualizado');
    }

    public function destroy(Insurer $insurer)
    {
        $insurer->delete();
        return $this->success(null, 'Insurer eliminado correctamente', 204);
    }
}
