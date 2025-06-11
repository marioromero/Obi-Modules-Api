<?php

namespace Modules\Configurations\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;
use Modules\Configurations\Models\Configuration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConfigurationController extends BaseApiController
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
        $paginator = Configuration::paginate(15);
        return $this->paginated($paginator, 'Listado de configurations');
    }

    public function show(Configuration $configuration)
    {
        return $this->success($configuration, 'Configuration obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $configuration = Configuration::create($data);

        return $this->success($configuration, 'Configuration creado correctamente', 201);
    }

    public function update(Request $request, Configuration $configuration)
    {
        $data = $request->validate(['name' => 'required|string']);
        $configuration->update($data);

        return $this->success($configuration, 'Configuration actualizado correctamente');
    }

    public function patch(Request $request, Configuration $configuration)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $configuration->update($data);

        return $this->success($configuration, 'Configuration parcialmente actualizado');
    }

    public function destroy(Configuration $configuration)
    {
        $configuration->delete();
        return $this->success(null, 'Configuration eliminado correctamente', 204);
    }
}
