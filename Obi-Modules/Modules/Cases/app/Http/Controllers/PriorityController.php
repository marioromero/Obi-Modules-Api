<?php

namespace Modules\Cases\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Cases\Models\Priority;
use App\Http\Controllers\Controller;


class PriorityController extends BaseApiController
{
    
    public function index()
    {
        $paginator = Priority::paginate(15);
        return $this->paginated($paginator, 'Listado de priorities');
    }

    public function show(Priority $priority)
    {
        return $this->success($priority, 'Priority obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $priority = Priority::create($data);

        return $this->success($priority, 'Priority creado correctamente', 201);
    }

    public function update(Request $request, Priority $priority)
    {
        $data = $request->validate(['name' => 'required|string']);
        $priority->update($data);

        return $this->success($priority, 'Priority actualizado correctamente');
    }

    public function patch(Request $request, Priority $priority)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $priority->update($data);

        return $this->success($priority, 'Priority parcialmente actualizado');
    }

    public function destroy(Priority $priority)
    {
        $priority->delete();
        return $this->success(null, 'Priority eliminado correctamente', 204);
    }
}

