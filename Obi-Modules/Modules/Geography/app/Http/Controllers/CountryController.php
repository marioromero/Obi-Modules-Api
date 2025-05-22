<?php

namespace Modules\Geography\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Geography\Models\Country;
use App\Http\Controllers\Controller;


class CountryController extends BaseApiController
{

    public function index()
    {
        $paginator = Country::paginate(15);
        return $this->paginated($paginator, 'Listado de countries');
    }

    public function show(Country $country)
    {
        return $this->success($country, 'Country obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $country = Country::create($data);

        return $this->success($country, 'Country creado correctamente', 201);
    }

    public function update(Request $request, Country $country)
    {
        $data = $request->validate(['name' => 'required|string']);
        $country->update($data);

        return $this->success($country, 'Country actualizado correctamente');
    }

    public function patch(Request $request, Country $country)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $country->update($data);

        return $this->success($country, 'Country parcialmente actualizado');
    }

    public function destroy(Country $country)
    {
        $country->delete();
        return $this->success(null, 'Country eliminado correctamente', 204);
    }
}
