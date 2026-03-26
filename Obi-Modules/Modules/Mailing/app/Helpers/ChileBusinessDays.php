<?php

namespace Modules\Mailing\app\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ChileBusinessDays
{
    private const TZ = 'America/Santiago';

    public static function isBusinessDay(Carbon $date): bool
    {
        $date = $date->copy()->setTimezone(self::TZ);

        if ($date->isSaturday() || $date->isSunday()) {
            return false;
        }

        $year = (int) $date->format('Y');
        $holidays = self::holidays($year); // ['YYYY-MM-DD', ...]

        return ! in_array($date->format('Y-m-d'), $holidays, true);
    }

    public static function holidays(int $year): array
    {
        $cacheKey = "cl_holidays_{$year}";

        return Cache::remember($cacheKey, now()->addDays(60), function () use ($year) {
            try {
                $res = Http::timeout(8)->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/CL");
                if (! $res->ok()) {
                    return [];
                }

                $json = $res->json() ?? [];
                $dates = [];

                foreach ($json as $row) {
                    if (! empty($row['date'])) {
                        $dates[] = (string) $row['date']; // YYYY-MM-DD
                    }
                }

                return array_values(array_unique($dates));
            } catch (\Throwable $e) {
                return [];
            }
        });
    }
}
