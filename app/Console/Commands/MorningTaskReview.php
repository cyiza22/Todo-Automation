<?php

namespace App\Console\Commands;

use App\Mail\TaskAlertMail;
use App\Mcp\Prompts\DailySummaryPrompt;
use App\Models\Todo;
use Carbon\Carbon;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
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
                if (config('groq.api_key')) {
                    $response = Http::withToken(config('groq.api_key'))->acceptJson()
                        ->timeout((int) config('groq.request_timeout', 30))
                        ->post(rtrim(config('groq.base_url'), '/').'/chat/completions', [
                            'model' => config('groq.model'),
                            'messages' => [['role' => 'user', 'content' => $prompt]],
                        ])->throw()->json();
                    $summaryText = $response['choices'][0]['message']['content'] ?? '';
                } else {
                    $response = Gemini::generativeModel(model: 'gemini-2.5-flash')->generateContent($prompt);
                    $summaryText = $response->text();
                }
                $this->info('Morning AI summary generated successfully.');
            } catch (\Throwable $exception) {
                if (config('groq.api_key') && config('gemini.api_key')) {
                    try {
                        $summaryText = Gemini::generativeModel(model: 'gemini-2.5-flash')->generateContent($prompt)->text();
                        $this->warn('Groq is unavailable; generated the summary with Gemini.');
                    } catch (\Throwable) {
                        $summaryText = '';
                    }
                }
                if (empty($summaryText)) {
                    $taskContext = $dailySummaryPrompt->formatTaskContext($tasks, $timezone);
                    $summaryText = "AI summary is temporarily unavailable. Tasks due today:\n\n{$taskContext}";
                    $this->warn('AI providers are unavailable; sending the task list without an AI summary.');
                }
            }
        }

        $recipient = config('mail.task_alert_recipient');
        if (!$recipient) {
            $this->error('EMAIL_ADDRESS is not configured.');

            return self::FAILURE;
        }

        Mail::to($recipient)->queue(new TaskAlertMail($summaryText, '', 'Your morning task summary'));
        $message = config('queue.default') === 'sync'
            ? 'Morning task summary email handed to the mailer.'
            : 'Morning task summary email queued. Run `php artisan queue:work` to deliver it.';
        $this->info($message);

        return self::SUCCESS;
    }
}
