<?php

namespace Modules\Users\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;
use Modules\Users\Models\Model;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModelController extends BaseApiController
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
        $paginator = Model::paginate(15);
        return $this->paginated($paginator, 'Listado de models');
    }

    public function show(Model $model)
    {
        return $this->success($model, 'Model obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $model = Model::create($data);

        return $this->success($model, 'Model creado correctamente', 201);
    }

    public function update(Request $request, Model $model)
    {
        $data = $request->validate(['name' => 'required|string']);
        $model->update($data);

        return $this->success($model, 'Model actualizado correctamente');
    }

    public function patch(Request $request, Model $model)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $model->update($data);

        return $this->success($model, 'Model parcialmente actualizado');
    }

    public function destroy(Model $model)
    {
        $model->delete();
        return $this->success(null, 'Model eliminado correctamente', 204);
    }
}
