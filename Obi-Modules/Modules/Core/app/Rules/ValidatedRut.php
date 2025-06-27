<?php

namespace Modules\Core\app\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Core\app\Helpers\RutValidator;   // Helper ubicado en Modules/Core/app/Helpers

class ValidatedRut implements Rule
{
    /**
     * Verifica si el RUT es válido.
     */
    public function passes($attribute, $value): bool
    {
        return RutValidator::isValidRut($value);
    }

    /**
     * Mensaje de error por defecto.
     */
    public function message(): string
    {
        return 'El RUT no es válido.';
    }
}
