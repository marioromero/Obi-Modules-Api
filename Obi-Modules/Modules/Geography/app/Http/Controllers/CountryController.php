<?php

namespace Modules\Geography\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Geography\Models\Country;
use App\Http\Controllers\Controller;


class CountryController extends Controller
{

    public function index()
    {
        $data = Country::paginate(15);
        return response()->json($data);
    }

    public function show(Country $country)
    {
        return response()->json($country);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $country = Country::create($data);
        return response()->json($country, 201);
    }

    public function update(Request $request, Country $country)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $country->update($data);
        return response()->json($country);
    }

    public function patch(Request $request, Country $country)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $country->update($data);
        return response()->json($country);
    }

    public function destroy(Country $country)
    {
        $country->delete();
        return response()->noContent();
    }
}
