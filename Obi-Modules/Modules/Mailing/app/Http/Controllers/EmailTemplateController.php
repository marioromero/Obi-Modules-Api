<?php

namespace Modules\Mailing\app\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mailing\Models\EmailTemplate;
use App\Http\Controllers\Controller;


class EmailTemplateController extends Controller
{

    public function index()
    {
        $data = EmailTemplate::paginate(15);
        return response()->json($data);
    }

    public function show(EmailTemplate $emailTemplate)
    {
        return response()->json($emailTemplate);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $emailTemplate = EmailTemplate::create($data);
        return response()->json($emailTemplate, 201);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $emailTemplate->update($data);
        return response()->json($emailTemplate);
    }

    public function patch(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
        ]);
        $emailTemplate->update($data);
        return response()->json($emailTemplate);
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();
        return response()->noContent();
    }
}
