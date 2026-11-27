<?php

namespace Modules\Core\app\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UfHistoryService
{
    public function __construct(
        private readonly UfHistoryRepository $repository,
        private readonly BancoCentralService $bancoCentralService
    ) {}

    /**
     * Devuelve la UF desde el archivo histórico, extendiéndolo si la fecha
     * solicitada queda fuera del rango actual.
     */
    public function resolve(string $date): ?array
    {
        if (! config('services.uf.history_enabled', true)) {
            return null;
        }

        try {
            $entry = $this->repository->get($date);

            if ($entry !== null) {
                return $this->result($entry);
            }

            if (! $this->ensureDate($date)) {
                return null;
            }

            $entry = $this->repository->get($date);

            return $entry === null ? null : $this->result($entry);
        } catch (\Throwable $e) {
            Log::warning('UF history: no se pudo leer o extender el archivo', [
                'fecha' => $date,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Asegura que una fecha exista en el archivo. Extiende hacia adelante o
     * hacia atrás según corresponda.
     */
    public function ensureDate(string $date): bool
    {
        if (! $this->repository->exists() || $this->repository->firstDate() === null) {
            $this->initialize();
        }

        $first = $this->repository->firstDate();
        $last = $this->repository->lastDate();

        if ($first === null || $last === null) {
            return false;
        }

        if ($date < $first) {
            $this->fillRange(Carbon::parse($date), Carbon::parse($first)->subDay());
        } elseif ($date > $last) {
            $this->fillRange(Carbon::parse($last)->addDay(), Carbon::parse($date));
        } elseif ($this->repository->get($date) === null) {
            $this->fillRange(Carbon::parse($date), Carbon::parse($date));
        }

        return $this->repository->get($date) !== null;
    }

    /**
     * Inicializa el archivo con los últimos N meses.
     */
    public function initialize(int $months = 5, ?string $to = null): int
    {
        $toDate = $to !== null ? Carbon::parse($to)->startOfDay() : now()->startOfDay();
        $fromDate = $toDate->copy()->subMonthsNoOverflow(max(1, $months));

        return $this->fillRange($fromDate, $toDate);
    }

    /**
     * Sincroniza desde el último día registrado hasta la fecha indicada
     * (por defecto, hoy).
     */
    public function syncUntil(?string $to = null): int
    {
        $toDate = $to !== null ? Carbon::parse($to)->startOfDay() : now()->startOfDay();

        if (! $this->repository->exists() || $this->repository->lastDate() === null) {
            return $this->initialize(5, $toDate->format('Y-m-d'));
        }

        $fromDate = Carbon::parse($this->repository->lastDate())->addDay();

        if ($fromDate->gt($toDate)) {
            return 0;
        }

        return $this->fillRange($fromDate, $toDate);
    }

    /**
     * Consulta el rango en Banco Central y lo fusiona en el archivo.
     */
    public function fillRange(Carbon $from, Carbon $to): int
    {
        if ($from->gt($to)) {
            return 0;
        }

        $response = $this->bancoCentralService->getUfRange($from, $to);

        if (! $response->success || empty($response->data) || ! is_array($response->data)) {
            return 0;
        }

        return $this->repository->merge($response->data, 'banco_central');
    }

    public function bounds(): array
    {
        return [
            'first_date' => $this->repository->firstDate(),
            'last_date' => $this->repository->lastDate(),
            'path' => $this->repository->path(),
        ];
    }

    private function result(array $entry): array
    {
        return [
            'success' => true,
            'valor' => $entry['valor'],
            'fecha' => $entry['fecha'],
            'fecha_valor' => $entry['fecha'],
            'fuente' => $entry['fuente'],
            'origen' => 'archivo',
            'ultima_publicada' => false,
        ];
    }
}
