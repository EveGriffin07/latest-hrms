<?php

namespace App\Services;

use Carbon\Carbon;

class OvertimeDayService
{
    public const TYPE_NORMAL = 'NORMAL';
    public const TYPE_REST_DAY = 'REST_DAY';
    public const TYPE_PUBLIC_HOLIDAY = 'PUBLIC_HOLIDAY';

    /**
     * Determine logical day type for overtime purposes.
     * Currently uses weekend + configured holidays; can be extended with per-employee schedules.
     */
    public static function getDayType(int $employeeId, Carbon $date): string
    {
        $dateStr = $date->format('Y-m-d');
        $holidays = config('hrms.overtime.holidays', []);

        if (in_array($dateStr, $holidays, true)) {
            return self::TYPE_PUBLIC_HOLIDAY;
        }

        if ($date->isWeekend()) {
            return self::TYPE_REST_DAY;
        }

        return self::TYPE_NORMAL;
    }

    public static function getRateForDayType(string $dayType): float
    {
        switch ($dayType) {
            case self::TYPE_PUBLIC_HOLIDAY:
                return (float) config('hrms.overtime.multiplier_holiday', 3.0);
            case self::TYPE_REST_DAY:
                return (float) config('hrms.overtime.multiplier_weekend', 2.0);
            case self::TYPE_NORMAL:
            default:
                return (float) config('hrms.overtime.multiplier_weekday', 1.5);
        }
    }

    /**
     * Get official company work end time (not attendance), in minutes since midnight.
     * Uses config('hrms.overtime.official_end_time', '18:00').
     */
    public static function getOfficialClockOutMinutes(int $employeeId, Carbon $date): ?int
    {
        $time = config('hrms.overtime.official_end_time', '18:00');
        try {
            $t = Carbon::createFromFormat('H:i', $time);
        } catch (\Throwable $e) {
            return null;
        }
        return $t->hour * 60 + $t->minute;
    }

    public static function labelForDayType(string $dayType): string
    {
        return match ($dayType) {
            self::TYPE_PUBLIC_HOLIDAY => 'Public holiday',
            self::TYPE_REST_DAY => 'Rest day / weekend',
            default => 'Normal working day',
        };
    }
}

