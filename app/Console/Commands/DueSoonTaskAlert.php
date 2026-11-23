<?php

namespace App\Console\Commands;

use App\Mail\TaskAlertMail;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class DueSoonTaskAlert extends Command
{
    protected $signature = 'app:due-soon-task-alert';

    protected $description = 'Send an alert for incomplete tasks due within the next two hours';

    public function handle(): int
    {
        $timezone = config('app.task_timezone', config('app.timezone'));
        $now = Carbon::now($timezone);

        $tasks = Todo::query()
            ->where('completed', false)
            ->where('due_date', '>=', $now->copy()->utc())
            ->where('due_date', '<=', $now->copy()->addHours(2)->utc())
            ->get();

        $unalertedTasks = $tasks->reject(fn (Todo $task) => Cache::has($this->cacheKey($task)));

        if ($unalertedTasks->isEmpty()) {
            $this->info('No new tasks are due within the next two hours.');

            return self::SUCCESS;
        }

        $recipient = config('mail.task_alert_recipient');
        if (!$recipient) {
            $this->error('EMAIL_ADDRESS is not configured.');

            return self::FAILURE;
        }

        $emailContent = "You have tasks due within the next 2 hours:\n\n";
        foreach ($unalertedTasks as $task) {
            $dueAt = $task->due_date->copy()->setTimezone($timezone);
            $emailContent .= "- {$task->title} (Due: {$dueAt->format('D, M j · H:i')})\n";
        }

        Mail::to($recipient)->queue(new TaskAlertMail('', $emailContent, 'Tasks due in the next 2 hours'));

        foreach ($unalertedTasks as $task) {
            $secondsUntilDue = max(60, $now->diffInSeconds($task->due_date->copy()->setTimezone($timezone), false));
            Cache::put($this->cacheKey($task), true, $secondsUntilDue + 86400);
        }

        $message = config('queue.default') === 'sync'
            ? 'Due-soon task alert email handed to the mailer.'
            : 'Due-soon task alert email queued. Run `php artisan queue:work` to deliver it.';
        $this->info($message);

        return self::SUCCESS;
    }

    private function cacheKey(Todo $task): string
    {
        return "task-alert:{$task->id}:{$task->due_date?->timestamp}";
    }
}
