<?php

namespace App\Support;

use Carbon\Carbon;

class TodoDueDate
{
    public static function combine(?string $date, ?string $time, ?Carbon $existing = null): ?string
    {
        if ($date === null && $time === null) {
            return $existing?->format('Y-m-d H:i:s');
        }

        $timezone = config('app.task_timezone', config('app.timezone'));
        $dueDate = $date !== null
            ? Carbon::parse($date, $timezone)
            : $existing?->copy()->timezone($timezone);

        if ($dueDate === null) {
            return null;
        }

        if ($time !== null) {
            $dueDate->setTimeFromTimeString($time);
        } elseif ($existing !== null && $date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dueDate->setTime($existing->hour, $existing->minute, $existing->second);
        }

        return $dueDate->utc()->format('Y-m-d H:i:s');
    }
}
