<?php

namespace Modules\Core\app\Helpers;

class RutValidator
{
    public static function isValidRut($rut)
    {
        // Verifica el formato del RUT
        if (!preg_match("/^\d{7,8}-[0-9Kk]{1}$/", $rut)) {
            return false;
        }

        // Separa el número y el dígito verificador
        list($number, $dv) = explode('-', $rut);

        // Calcula el dígito verificador
        $sum = 0;
        $multiplier = 2;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $sum += $number[$i] * $multiplier;
            $multiplier = $multiplier == 7 ? 2 : $multiplier + 1;
        }

        $expectedDv = 11 - ($sum % 11);
        if ($expectedDv == 11) {
            $expectedDv = 0;
        } elseif ($expectedDv == 10) {
            $expectedDv = 'K';
        }

        // Compara el dígito verificador esperado con el ingresado
        return strtoupper($dv) == $expectedDv;
    }
}
