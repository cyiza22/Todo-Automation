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

        $timezone = trim(config('app.task_timezone', config('app.timezone', 'Africa/Kigali')));
        $dueDate = null;

        if ($date !== null) {
            $date = trim($date);

            try {
                // 1. Force strict match for your layout: DD-MM-YYYY HH:MM
                if (preg_match('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}$/', $date)) {
                    $dueDate = Carbon::createFromFormat('d-m-Y H:i', $date, $timezone);
                } 
                // 2. Strict match for just the date: DD-MM-YYYY
                elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
                    $dueDate = Carbon::createFromFormat('d-m-Y', $date, $timezone)->startOfDay();
                } 
                // 3. Fallback only if it matches standard Laravel/ISO formats
                else {
                    $dueDate = Carbon::parse($date)->shiftTimezone($timezone);
                }
            } catch (\Exception $e) {
                return null;
            }
        } elseif ($existing !== null) {
            $dueDate = $existing->copy()->setTimezone($timezone);
        }

        if ($dueDate === null) {
            return null;
        }

        if ($time !== null) {
            $dueDate->setTimeFromTimeString($time);
        }

        // Return a clean database string converted into absolute UTC
        return $dueDate->utc()->format('Y-m-d H:i:s');
    }
}
