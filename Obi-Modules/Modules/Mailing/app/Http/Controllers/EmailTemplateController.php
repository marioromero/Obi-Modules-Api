<?php

namespace Modules\Mailing\app\Http\Controllers;
use Modules\Core\App\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Mailing\Models\EmailTemplate;
use App\Http\Controllers\Controller;


class EmailTemplateController extends BaseApiController
{

    public function index()
    {
        $paginator = EmailTemplate::paginate(15);
        return $this->paginated($paginator, 'Listado de email-templates');
    }

    public function show(EmailTemplate $emailTemplate)
    {
        return $this->success($emailTemplate, 'EmailTemplate obtenido correctamente');
    }

    public function store(Request $request)
    {
        $data   = $request->validate(['name' => 'required|string']);
        $emailTemplate = EmailTemplate::create($data);

        return $this->success($emailTemplate, 'EmailTemplate creado correctamente', 201);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate(['name' => 'required|string']);
        $emailTemplate->update($data);

        return $this->success($emailTemplate, 'EmailTemplate actualizado correctamente');
    }

    public function patch(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate(['name' => 'sometimes|string']);
        $emailTemplate->update($data);

        return $this->success($emailTemplate, 'EmailTemplate parcialmente actualizado');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();
        return $this->success(null, 'EmailTemplate eliminado correctamente', 204);
    }
}
