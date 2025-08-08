<?php

namespace Modules\Configurations\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;
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

    /* ───────────────  Países  (type_id = 2)  ─────────────── */

    /** Devuelve los países configurados */
    public function countries()
    {
        $configuration = Configuration::where('type_id', 2)->firstOrFail();   // Global_geography
        $ids = $configuration->content['countries'] ?? [];

        $countries = Country::whereIn('id', $ids)
                            ->get(['id', 'demonym_female']);

        return $this->success($countries, 'Countries from configuration');
    }

    /** Actualiza la lista de países */
    public function updateCountries(Request $request, UpdateCountries $service)
    {
        $data = $request->validate([
            'countries'   => ['required', 'array'],
            'countries.*' => ['integer', Rule::exists('geography_db.countries', 'id')],
        ]);

        $configuration = Configuration::where('type_id', 2)->firstOrFail();
        $config        = $service($configuration, $data['countries']);

        return $this->success($config, 'Countries list updated');
    }

    /* ───────  Responsabilidades de usuario (type_id = 4)  ─────── */

    public function getUserResponsibilities()
    {
        $configuration = Configuration::where('type_id', 4)->firstOrFail();
        return $this->success(
            $configuration->content,
            'Responsabilidades de usuarios obtenidas correctamente'
        );
    }

    public function updateUserResponsibilities(Request $request)
    {
        $configuration = Configuration::where('type_id', 4)->firstOrFail();

        $configuration->content = array_merge(
            $configuration->content ?? [],
            $request->all()
        );
        $configuration->save();

        return $this->success(
            $configuration,
            'Responsabilidades de usuarios actualizadas correctamente'
        );
    }
}
