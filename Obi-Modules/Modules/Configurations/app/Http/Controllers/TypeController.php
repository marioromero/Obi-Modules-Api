<?php

namespace Modules\Configurations\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;
use Modules\Configurations\Models\Type;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TypeController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    

    /**
     * Store a newly created resource in storage.
     */
    

    /**
     * Show the specified resource.
     */
    

    /**
     * Update the specified resource in storage.
     */
    

    /**
     * Remove the specified resource from storage.
     */
    

    public function index()
    {
        $paginator = Type::paginate(15);
        return $this->paginated($paginator, 'Listado de types');
    }

    public function show(Type $type)
    {
        return $this->success($type, 'Type obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $type = Type::create($data);

        return $this->success($type, 'Type creado correctamente', 201);
    }

    public function update(Request $request, Type $type)
    {
        $data = $request->validate(['name' => 'required|string']);
        $type->update($data);

        return $this->success($type, 'Type actualizado correctamente');
    }

    public function patch(Request $request, Type $type)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $type->update($data);

        return $this->success($type, 'Type parcialmente actualizado');
    }

    public function destroy(Type $type)
    {
        $type->delete();
        return $this->success(null, 'Type eliminado correctamente', 204);
    }
}
