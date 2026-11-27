<?php

namespace Modules\Cases\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterBudgetSendingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'                 => 'required|string|max:50',
            'budget_sending_date'  => 'sometimes|nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'                => 'El código del caso es obligatorio.',
            'code.string'                  => 'El código del caso debe ser texto.',
            'budget_sending_date.date'     => 'La fecha de envío de presupuesto debe ser una fecha válida.',
        ];
    }
}
