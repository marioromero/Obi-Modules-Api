<?php

namespace Modules\Banks\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Banks\Models\Bank;
use Modules\Core\app\Http\BaseApiController;

class BankController extends BaseApiController
{
    public function index()
    {
        $banks = Bank::all();
        return $this->success($banks, 'Listado de bancos', 200);
    }

    public function show(Bank $bank)
    {
        return $this->success($bank, 'Banco obtenido correctamente', 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|min:1|max:100']);
        $bank = Bank::create(['name' => trim($data['name'])]);

        return $this->success($bank, 'Banco creado correctamente', 201);
    }

    public function update(Request $request, Bank $bank)
    {
        $data = $request->validate(['name' => 'required|string']);
        $bank->update($data);

        return $this->success($bank, 'Banco actualizado correctamente', 200);
    }

    public function patch(Request $request, Bank $bank)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $bank->update($data);

        return $this->success($bank, 'Banco parcialmente actualizado', 200);
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();
        return $this->success(null, 'Banco eliminado correctamente', 200);
    }
}