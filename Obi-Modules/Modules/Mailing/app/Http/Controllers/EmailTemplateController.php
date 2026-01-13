<?php

namespace Modules\Mailing\app\Http\Controllers;
use Modules\Core\app\Http\BaseApiController;

use Illuminate\Http\Request;
use Modules\Mailing\Models\EmailTemplate;
use App\Http\Controllers\Controller;
use Modules\Core\app\Helpers\ColumnMap;


class EmailTemplateController extends BaseApiController
{

    public function index()
    {
        $keys = [
            'id',
            'name',
            'content',
            'department',
            'user_name',
        ];

        $rows = EmailTemplate::query()
            ->with([
                'department:id,name,email',
                'user:id,name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function ($t) {
                $t->user_name = $t->user?->name;
                unset($t->user);
                return $t;
            });

        return $this->success([
            'columns' => ColumnMap::translate($keys, 'mailing'),
            'rows'    => $rows,
        ], 'Listado de email-templates');
    }



    public function show(EmailTemplate $emailTemplate)
    {
        return $this->success($emailTemplate, 'Plantilla obtenidas correctamente');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:50',
            'department_id' => 'required|integer',
            'content'       => 'required|string',
            'user_id'       => 'nullable|integer', // fallback si no hay auth
        ]);

        $userId = auth()->id() ?? ($data['user_id'] ?? null);

        if (! $userId) {
            return $this->error(
                'No hay usuario autenticado (user_id es requerido si no usas auth).',
                422
            );
        }

        $emailTemplate = EmailTemplate::create([
            'name'          => $data['name'],
            'department_id' => $data['department_id'],
            'content'       => $data['content'],
            'user_id'       => $userId,
        ]);

        return $this->success($emailTemplate, 'Plantilla creada correctamente', 200);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:50',
            'department_id' => 'sometimes|integer',
            'content'       => 'sometimes|string',
        ]);

        if (empty($data)) {
            return $this->error('No se enviaron campos para actualizar.', 422);
        }

        $emailTemplate->update($data);

        return $this->success($emailTemplate, 'Plantilla actualizada correctamente', 200);
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
        return $this->success(null, 'Plantilla eliminada correctamente', 200);
    }
}

