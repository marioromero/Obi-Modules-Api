<?php

namespace Modules\Core\app\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UfHistoryRepository
{
    public function path(): string
    {
        $relative = (string) config('services.uf.history_path', 'uf/uf-history.json');

        return Storage::disk('local')->path($relative);
    }

    public function exists(): bool
    {
        return is_file($this->path());
    }

    public function read(): array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return $this->empty();
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return $this->empty();
        }

        try {
            if (! flock($handle, LOCK_SH)) {
                return $this->empty();
            }

            $contents = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        $decoded = json_decode((string) $contents, true);

        return $this->normalize(is_array($decoded) ? $decoded : []);
    }

    public function get(string $date): ?array
    {
        $data = $this->read();
        $entry = $data['values'][$date] ?? null;

        if (! is_array($entry) || ! is_numeric($entry['valor'] ?? null)) {
            return null;
        }

        return [
            'fecha' => $date,
            'valor' => (float) $entry['valor'],
            'fuente' => (string) ($entry['fuente'] ?? 'banco_central'),
        ];
    }

    public function firstDate(): ?string
    {
        return $this->read()['first_date'] ?? null;
    }

    public function lastDate(): ?string
    {
        return $this->read()['last_date'] ?? null;
    }

    /**
     * Inserta o actualiza observaciones.
     *
     * Acepta una lista de ['fecha' => 'Y-m-d', 'valor' => float] o un mapa
     * ['Y-m-d' => float].
     */
    public function merge(array $observations, string $source = 'banco_central'): int
    {
        $normalized = [];

        foreach ($observations as $key => $observation) {
            $entry = $this->normalizeObservation($key, $observation);

            if ($entry !== null) {
                $normalized[$entry['fecha']] = $entry['valor'];
            }
        }

        if (empty($normalized)) {
            return 0;
        }

        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("No se pudo crear el directorio de UF: {$directory}");
        }

        $lockPath = $path.'.lock';
        $lock = @fopen($lockPath, 'c');

        if ($lock === false) {
            throw new RuntimeException("No se pudo abrir el lock de UF: {$lockPath}");
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('No se pudo bloquear el archivo de UF');
            }

            $data = $this->read();
            $written = 0;

            foreach ($normalized as $fecha => $valor) {
                if (($data['values'][$fecha]['valor'] ?? null) !== $valor) {
                    $written++;
                }

                $data['values'][$fecha] = [
                    'valor' => $valor,
                    'fuente' => $source,
                ];
            }

            ksort($data['values']);

            $data['version'] = 1;
            $data['series'] = (string) config('services.bcentral.uf_series', 'F073.UFF.PRE.Z.D');
            $data['updated_at'] = now()->toIso8601String();
            $data['first_date'] = array_key_first($data['values']);
            $data['last_date'] = array_key_last($data['values']);

            $json = json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            if ($json === false) {
                throw new RuntimeException('No se pudo serializar el historial de UF');
            }

            $temporary = $path.'.tmp.'.getmypid().'.'.bin2hex(random_bytes(4));

            if (file_put_contents($temporary, $json.PHP_EOL) === false) {
                throw new RuntimeException("No se pudo escribir el temporal de UF: {$temporary}");
            }

            if (! @rename($temporary, $path)) {
                if (! @copy($temporary, $path)) {
                    @unlink($temporary);

                    throw new RuntimeException("No se pudo reemplazar el archivo de UF: {$path}");
                }

                @unlink($temporary);
            }

            @chmod($path, 0664);

            return $written;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function normalize(array $data): array
    {
        $values = [];

        if (isset($data['values']) && is_array($data['values'])) {
            foreach ($data['values'] as $date => $entry) {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
                    continue;
                }

                if (is_array($entry) && is_numeric($entry['valor'] ?? null)) {
                    $values[$date] = [
                        'valor' => (float) $entry['valor'],
                        'fuente' => (string) ($entry['fuente'] ?? 'banco_central'),
                    ];
                } elseif (is_numeric($entry)) {
                    $values[$date] = [
                        'valor' => (float) $entry,
                        'fuente' => 'banco_central',
                    ];
                }
            }
        }

        ksort($values);

        return [
            'version' => 1,
            'series' => (string) ($data['series'] ?? config('services.bcentral.uf_series', 'F073.UFF.PRE.Z.D')),
            'updated_at' => $data['updated_at'] ?? null,
            'first_date' => empty($values) ? null : array_key_first($values),
            'last_date' => empty($values) ? null : array_key_last($values),
            'values' => $values,
        ];
    }

    private function empty(): array
    {
        return [
            'version' => 1,
            'series' => (string) config('services.bcentral.uf_series', 'F073.UFF.PRE.Z.D'),
            'updated_at' => null,
            'first_date' => null,
            'last_date' => null,
            'values' => [],
        ];
    }

    private function normalizeObservation(int|string $key, mixed $observation): ?array
    {
        if (is_array($observation)) {
            $fecha = $observation['fecha'] ?? $observation['date'] ?? null;
            $valor = $observation['valor'] ?? $observation['value'] ?? null;
        } else {
            $fecha = is_string($key) ? $key : null;
            $valor = $observation;
        }

        if (! is_string($fecha) || ! is_numeric($valor)) {
            return null;
        }

        try {
            $date = Carbon::parse($fecha)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }

        return ['fecha' => $date, 'valor' => (float) $valor];
    }
}
