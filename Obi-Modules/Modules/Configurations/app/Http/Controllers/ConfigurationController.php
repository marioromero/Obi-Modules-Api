<?php

namespace Modules\Configurations\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;
use Modules\Configurations\Models\Configuration;
use Modules\Configurations\app\Services\UpdateCountries;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Geography\Models\Country;

class ConfigurationController extends BaseApiController
{

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
/** Devuelve los gentilicios femeninos definidos en la config */
    public function countries(Configuration $configuration)
    {
        // ➊ Asegúrate de castear 'content' a array (ya sea vía cast o decode)
        $ids = $configuration->content['countries'] ?? [];

        // ➋ Trae solo esos países y solo las columnas necesarias
        $countries = Country::whereIn('id', $ids)
            ->get(['id', 'demonym_female']);

        return $this->success($countries, 'Countries from configuration');
    }
    public function updateCountries(
            Request          $request,
            Configuration    $configuration,
            UpdateCountries  $service,
        ) {
            // ➊ Valida que llegue un array y que cada id exista en geography.countries
            $data = $request->validate([
                'countries'   => ['required', 'array'],
                'countries.*' => [
                    'integer',
                    Rule::exists('geography_db.countries', 'id'),
                ],
            ]);

            // ➋ Llama al Service para actualizar
            $config = $service($configuration, $data['countries']);

            // ➌ Responde con wrapper success (200)
            return $this->success($config, 'Countries list updated');
        }
}
