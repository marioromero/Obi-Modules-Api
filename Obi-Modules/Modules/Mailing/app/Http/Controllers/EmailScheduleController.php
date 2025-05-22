<?php

namespace Modules\Mailing\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Mailing\Models\EmailSchedule;
use App\Http\Controllers\Controller;

class EmailScheduleController extends BaseApiController
{

    public function index()
    {
        $paginator = EmailSchedule::paginate(15);
        return $this->paginated($paginator, 'Listado de email-schedules');
    }

    public function show(EmailSchedule $emailSchedule)
    {
        return $this->success($emailSchedule, 'EmailSchedule obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $emailSchedule = EmailSchedule::create($data);

        return $this->success($emailSchedule, 'EmailSchedule creado correctamente', 201);
    }

    public function update(Request $request, EmailSchedule $emailSchedule)
    {
        $data = $request->validate(['name' => 'required|string']);
        $emailSchedule->update($data);

        return $this->success($emailSchedule, 'EmailSchedule actualizado correctamente');
    }

    public function patch(Request $request, EmailSchedule $emailSchedule)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $emailSchedule->update($data);

        return $this->success($emailSchedule, 'EmailSchedule parcialmente actualizado');
    }

    public function destroy(EmailSchedule $emailSchedule)
    {
        $emailSchedule->delete();
        return $this->success(null, 'EmailSchedule eliminado correctamente', 204);
    }
}
