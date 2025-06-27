<?php
// Modules/Configurations/app/Services/UpdateCountries.php

namespace Modules\Configurations\app\Services;

use Modules\Configurations\Models\Configuration;

/**
 * Reemplaza únicamente el arreglo `countries` dentro
 * del campo JSON `content` y guarda el registro.
 */
class UpdateCountries
{
    public function __invoke(Configuration $config, array $ids): Configuration
{
    // Convierte el string JSON a array; si ya es array, lo deja igual
    $content = is_array($config->content)
        ? $config->content
        : json_decode($config->content ?? '{}', true);

    $content['countries'] = $ids;

    // Re-asigna (Laravel lo volverá a string al guardar)
    $config->content = $content;
    $config->save();

    return $config->refresh();
}
}
