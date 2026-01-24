<?php

namespace Modules\Mailing\app\Helpers;

use Carbon\Carbon;

class MailingRunHelper
{
    public const TZ = 'America/Santiago';

    /**
     * Hora "now" en TZ Chile.
     */
    public static function nowChile(): Carbon
    {
        return Carbon::now(self::TZ);
    }

    /**
     * Devuelve el inicio de ventana (hoy a HH:MM:SS).
     */
    public static function windowStart(Carbon $now, string $windowStart): Carbon
    {
        return $now->copy()->setTimeFromTimeString($windowStart);
    }

    /**
     * Devuelve el fin de ventana (hoy a HH:MM:SS).
     */
    public static function windowEnd(Carbon $now, string $windowEnd): Carbon
    {
        return $now->copy()->setTimeFromTimeString($windowEnd);
    }

    /**
     * ¿Está dentro de la ventana [start, end)?
     */
    public static function isWithinWindow(Carbon $now, string $windowStart, string $windowEnd): bool
    {
        $start = self::windowStart($now, $windowStart);
        $end   = self::windowEnd($now, $windowEnd);

        return $now->gte($start) && $now->lt($end);
    }

    /**
     * Intervalo en segundos para la campaña (send_once).
     * Si viene null/0 o inválido, usa $fallback.
     */
    public static function intervalSeconds(?int $sendOnce, int $fallback = 25): int
    {
        $v = (int) ($sendOnce ?? 0);
        return $v > 0 ? $v : $fallback;
    }

    /**
     * Capacidad máxima por día según ventana e intervalo:
     * floor(segundosVentana / intervalo).
     */
    public static function maxPerDay(string $windowStart, string $windowEnd, int $intervalSeconds): int
    {
        $base = Carbon::now(self::TZ);

        $start = $base->copy()->setTimeFromTimeString($windowStart);
        $end   = $base->copy()->setTimeFromTimeString($windowEnd);

        // Si end <= start (no debería), devolvemos 0.
        if ($end->lte($start)) {
            return 0;
        }

        $windowSeconds = $start->diffInSeconds($end); // positivo
        return max(1, (int) floor($windowSeconds / max(1, $intervalSeconds)));
    }

    /**
     * Cuántos envíos caben con el tiempo restante de ventana hoy.
     * OJO: el $maxCap normalmente es maxPerDay(...).
     */
    public static function maxSendsForRemainingWindow(
        Carbon $now,
        string $windowEnd,
        int $intervalSeconds,
        int $maxCap,
        bool $debug = false,
        ?callable $debugLogger = null
    ): int {
        $end = self::windowEnd($now, $windowEnd);

        if ($end->lte($now)) {
            if ($debug && $debugLogger) {
                $debugLogger("[DEBUG] now={$now->format('Y-m-d H:i:s')} end={$end->format('Y-m-d H:i:s')} (end<=now)");
            }
            return 0;
        }

        $remainingSeconds = $now->diffInSeconds($end); // positivo
        $byTime = (int) floor($remainingSeconds / max(1, $intervalSeconds));

        if ($debug && $debugLogger) {
            $debugLogger("[DEBUG] now={$now->format('Y-m-d H:i:s')} end={$end->format('Y-m-d H:i:s')} remaining={$remainingSeconds}s byTime={$byTime}");
        }

        return max(0, min($maxCap, $byTime));
    }

    /**
     * Próximo start_in para retry: mañana 08:00 (o próximo hábil 08:00 si onlyBusinessDays=true).
     */
    public static function nextRetryStartAt(
        bool $onlyBusinessDays,
        string $windowStart,
        callable $isBusinessDayFn // fn(Carbon $dt): bool
    ): Carbon {
        $dt = self::nowChile()
            ->addDay()
            ->setTimeFromTimeString($windowStart);

        if ($onlyBusinessDays) {
            while (! $isBusinessDayFn($dt)) {
                $dt = $dt->addDay()->setTimeFromTimeString($windowStart);
            }
        }

        return $dt;
    }
}
