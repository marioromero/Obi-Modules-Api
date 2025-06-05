<?php

namespace Modules\Configurations\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Configurations\Models\Configuration;
use Modules\Core\App\Http\BaseApiController;
use Illuminate\Validation\ValidationException;

class ConfigurationsController extends BaseApiController
{
    /**
     * Listar todas las configuraciones (paginado a 15 por página).
     */
    public function index(Request $request)
    {
        try {
            $paginator = Configuration::paginate(15);
            return $this->paginated($paginator, 'Listado de configuraciones');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Crear una nueva configuración.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'type_id'   => 'required|integer|exists:types,id',
                'content'   => 'required|array|min:1',
                'content.*' => 'integer|distinct',
            ]);

            $configuration = Configuration::create([
                'type_id' => $data['type_id'],
                'content' => $data['content'], // el mutator convierte el arreglo a JSON
            ]);

            return $this->success($configuration, 'Configuración creada', 201);
        } catch (ValidationException $ve) {
            return $this->error($ve->errors(), 422);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Mostrar una sola configuración por ID.
     */
    public function show(Configuration $configuration)
    {
        try {
            return $this->success($configuration, 'Configuración encontrada', 200);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Actualizar una configuración existente (PUT).
     */
    public function update(Request $request, Configuration $configuration)
    {
        try {
            $data = $request->validate([
                'type_id'   => 'required|integer|exists:types,id',
                'content'   => 'required|array|min:1',
                'content.*' => 'integer|distinct',
            ]);

            $configuration->type_id = $data['type_id'];
            $configuration->content = $data['content'];
            $configuration->save();

            return $this->success($configuration, 'Configuración actualizada', 200);
        } catch (ValidationException $ve) {
            return $this->error($ve->errors(), 422);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Eliminar una configuración (DELETE).
     */
    public function destroy(Configuration $configuration)
    {
        try {
            $configuration->delete();
            return $this->success(null, 'Configuración eliminada', 200);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
