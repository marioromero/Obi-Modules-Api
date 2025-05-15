<?php

namespace Modules\Banks\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Banks\Models\Bank;
use App\Http\Controllers\Controller;


class BankController extends Controller
{

    public function index()
    {
        $data = Bank::paginate(15);
        return response()->json($data);
    }

    public function show(Bank $bank)
    {
        return response()->json($bank);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $bank = Bank::create($data);
        return response()->json($bank, 201);
    }

    public function update(Request $request, Bank $bank)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $bank->update($data);
        return response()->json($bank);
    }

    public function patch(Request $request, Bank $bank)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $bank->update($data);
        return response()->json($bank);
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();
        return response()->noContent();
    }
}
