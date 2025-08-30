<?php

namespace Modules\Core\app\Helpers;

use Illuminate\Support\Str;

class ColumnMap
{
    // Recibe un array de keys en inglés y devuelve el mismo array en español (mismo orden/tamaño)
    public static function translate(array $keys, string $domain = 'cases'): array
    {
        return array_map(fn ($k) => self::label((string) $k, $domain), $keys);
    }

    // Renombra las keys de un registro (array asociativo) al español
    public static function renameKeys(array $row, string $domain = 'cases'): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $out[self::label((string) $k, $domain)] = $v;
        }
        return $out;
    }

    // Renombra las keys de una colección completa (iterable de arrays/objects)
    public static function renameCollection(iterable $rows, string $domain = 'cases'): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = self::renameKeys((array) $row, $domain);
        }
        return $out;
    }

    // Internos

    // Traduce una sola key usando el mapa del dominio, comunes y fallback
   protected static function label(string $key, string $domain): string
    {
        $k = strtolower($key);

        // 1) intenta leer desde config; si viene vacío, carga directo el archivo del módulo
        $maps = config('column_map');
        if (!is_array($maps) || empty($maps)) {
            $path = base_path('Modules/Core/Config/column_map.php'); // ruta real del módulo
            $maps = is_file($path) ? require $path : [];
        }

        // 2) aliases
        $aliases = $maps['aliases'] ?? [];
        $k = $aliases[$k] ?? $k;

        // 3) mapas
        $domainMap  = $maps[$domain]['map'] ?? [];
        $commonMap  = $maps['__common']['map'] ?? [];
        $exceptions = $maps['__common']['exceptions'] ?? [];

        // 4) si hay mapeo explícito, respétalo TAL CUAL (no aplicar style)
        if (array_key_exists($k, $domainMap)) return $domainMap[$k];
        if (array_key_exists($k, $commonMap)) return $commonMap[$k];

        // 5) solo el fallback se estiliza
        return self::style(self::fallback($k), $exceptions);
    }

    // Fallback: snake_case → "snake case"; *_id → "snake case (ID)"
    protected static function fallback(string $k): string
    {
        if (Str::endsWith($k, '_id')) {
            $base = Str::replaceLast('_id', '', $k);
            return str_replace('_', ' ', $base) . ' (ID)';
        }
        return str_replace('_', ' ', $k);
    }

    // Estilo: compacta espacios, primera palabra en mayúscula, reinyecta tokens (ID, RUT, UF, JSON, N°)
    protected static function style(string $label, array $exceptions): string
    {
        $label = trim(preg_replace('/\s+/u', ' ', $label));

        $lower  = mb_strtolower($label, 'UTF-8');
        $first  = mb_substr($lower, 0, 1, 'UTF-8');
        $styled = mb_strtoupper($first, 'UTF-8') . mb_substr($lower, 1, null, 'UTF-8');

        foreach ($exceptions as $tok) {
            $styled = preg_replace('/\b' . preg_quote($tok, '/') . '\b/ui', $tok, $styled);
        }

        return $styled;
    }
}
