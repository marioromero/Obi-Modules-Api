<?php

namespace Modules\Users\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModelLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'           => ['required','integer','min:1'],
            'model_id'          => ['required','integer','min:1'],
            'event_id'          => ['required','integer','min:1'],
            'details'           => ['required'],
        ];
    }
}
