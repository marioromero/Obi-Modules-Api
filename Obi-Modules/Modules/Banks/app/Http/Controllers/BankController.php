<?php

namespace Modules\Banks\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Banks\Models\Bank;
use Modules\Core\App\Http\BaseApiController;

class BankController extends BaseApiController
{
    public function index()
    {
        $paginator = Bank::paginate(15);
        return $this->paginated($paginator, 'Listado de bancos');
    }

    public function show(Bank $bank)
    {
        return $this->success($bank, 'Banco obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $bank = Bank::create($data);

        return $this->success($bank, 'Banco creado correctamente', 201);
    }

    public function update(Request $request, Bank $bank)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $bank->update($data);

        return $this->success($bank, 'Banco actualizado correctamente');
    }

    public function patch(Request $request, Bank $bank)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);

        $bank->update($data);

        return $this->success($bank, 'Banco parcialmente actualizado');
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();

        return $this->success(null, 'Banco eliminado correctamente', 204);
    }
}

