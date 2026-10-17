<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/** Works out the next run time of a service schedule. */
final class Schedule
{
    public const TYPES = ['daily', 'weekly', 'monthly'];

    /** @return list<string> valid, sorted, unique "HH:MM" values */
    public static function cleanTimes(array $times): array
    {
        $out = [];
        foreach ($times as $t) {
            if (is_string($t) && preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', trim($t))) {
                $out[] = trim($t);
            }
        }
        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }

    /**
     * Next moment after $after (UTC Carbon) that matches the schedule, or null.
     *
     * weekly days: 0 = Sunday ... 6 = Saturday.
     * monthly days: 1-31; a day after the month's end (e.g. 31 in April) runs on the last day.
     */
    public static function next(array $times, string $type, array $days, string $timezone, Carbon $after): ?Carbon
    {
        $times = self::cleanTimes($times);
        $days = array_map('intval', $days);
        if ($times === [] || ($type !== 'daily' && $days === [])) {
            return null;
        }

        $local = CarbonImmutable::instance($after)->setTimezone($timezone)->startOfDay();
        for ($i = 0; $i < 400; $i++) {
            $day = $local->addDays($i);
            if (! self::dayMatches($day, $type, $days)) {
                continue;
            }
            foreach ($times as $time) {
                [$h, $m] = array_map('intval', explode(':', $time));
                $at = $day->setTime($h, $m);
                if ($at->greaterThan($after)) {
                    return Carbon::instance($at->setTimezone('UTC')->toDateTime());
                }
            }
        }

        return null;
    }

    private static function dayMatches(CarbonImmutable $day, string $type, array $days): bool
    {
        return match ($type) {
            'weekly' => in_array($day->dayOfWeek, $days, true),
            'monthly' => in_array($day->day, $days, true)
                || ($day->day === $day->daysInMonth && max($days) > $day->daysInMonth),
            default => true,
        };
    }
}
