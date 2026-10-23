<?php

namespace App\Console\Commands;

use App\Mail\TaskAlertMail;
use App\Mcp\Prompts\DailySummaryPrompt;
use App\Models\Todo;
use Carbon\Carbon;
use Gemini\Exceptions\ErrorException as GeminiErrorException;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MorningTaskReview extends Command
{
    protected $signature = 'app:morning-task-review';

    protected $description = 'Review today\'s tasks and send a summary email';

    public function handle(): int
    {
        $timezone = config('app.task_timezone', config('app.timezone'));
        $todayStart = Carbon::now($timezone)->startOfDay();
        $tomorrowStart = $todayStart->copy()->addDay();

        $tasks = Todo::query()
            ->where('completed', false)
            ->where('due_date', '>=', $todayStart->copy()->utc())
            ->where('due_date', '<', $tomorrowStart->copy()->utc())
            ->get();

        $dailySummaryPrompt = app(DailySummaryPrompt::class);
        if ($tasks->isEmpty()) {
            $summaryText = 'No incomplete tasks are due today.';
        } else {
            $prompt = $dailySummaryPrompt->buildPrompt($tasks, $timezone);

            try {
                $response = Gemini::generativeModel(model: 'gemini-3.8-flash')->generateContent($prompt);
                $summaryText = $response->text();
                $this->info('Morning AI summary generated successfully.');
            } catch (GeminiErrorException $exception) {
                $taskContext = $dailySummaryPrompt->formatTaskContext($tasks, $timezone);
                $summaryText = "AI summary is temporarily unavailable. Tasks due today:\n\n{$taskContext}";
                $this->warn('Gemini is temporarily unavailable; sending the task list without an AI summary.');
            }
        }

        $now = Carbon::now($timezone);
        $twoHoursFromNow = $now->copy()->addHours(2);

        $criticalTasks = Todo::query()
            ->where('completed', false)
            ->where('due_date', '>=', $now->copy()->utc())
            ->where('due_date', '<=', $twoHoursFromNow->copy()->utc())
            ->get();

        if ($tasks->isEmpty() && $criticalTasks->isEmpty()) {
            $this->info('No tasks due today or within the next two hours.');

            return self::SUCCESS;
        }

        $emailContent = '';
        if ($criticalTasks->isNotEmpty()) {
            $emailContent = "Urgent Task Alert\n\nYou have tasks due within the next 2 hours:\n\n";

            foreach ($criticalTasks as $task) {
                $dueAt = $task->due_date->copy()->timezone($timezone);
                $emailContent .= "- {$task->title} (Due: {$dueAt->format('H:i')})\n";
            }
        }

        $recipient = config('mail.task_alert_recipient');
        if (!$recipient) {
            $this->error('EMAIL_ADDRESS is not configured.');

            return self::FAILURE;
        }

        Mail::to($recipient)->send(new TaskAlertMail($summaryText, $emailContent));
        $this->info($criticalTasks->isNotEmpty()
            ? 'Daily summary and urgent task alert email sent.'
            : 'Daily summary email sent. No tasks are due within the next two hours.');

        return self::SUCCESS;
    }
}
