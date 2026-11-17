<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // Send the morning summary once a day.
        $schedule->command('app:morning-task-review')
            ->dailyAt('07:00')
            ->timezone(config('app.task_timezone', config('app.timezone')))
            ->withoutOverlapping();

        // Check regularly for tasks entering the next two-hour window.
        $schedule->command('app:due-soon-task-alert')
            ->everyFifteenMinutes()
            ->timezone(config('app.task_timezone', config('app.timezone')))
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
