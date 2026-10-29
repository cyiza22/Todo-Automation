<?php

namespace App\Mcp\Prompts;

use App\Models\Todo;
use Carbon\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class DailySummaryPrompt extends Prompt
{
    protected string $description = 'provides a daily summary of the tasks';

    public function handle(Request $request): Response
    {
        $timezone = config('app.task_timezone', config('app.timezone'));
        
        // 1. Explicitly initialize the requested date in Africa/Kigali time
        $localDate = Carbon::createFromFormat('Y-m-d', $request->get('date'), $timezone);
        
        // 2. Bound the day strictly using the target local timezone boundaries
        $startUtc = $localDate->copy()->startOfDay()->setTimezone('UTC');
        $endUtc = $localDate->copy()->endOfDay()->setTimezone('UTC');

        $tasks = Todo::query()
            ->where('completed', false)
            ->where('due_date', '>=', $startUtc)
            ->where('due_date', '<=', $endUtc)
            ->get();

        return Response::text($this->buildPrompt($tasks, $timezone));
    }

    public function buildPrompt(iterable $tasks, string $timezone): string
    {
        $taskContext = $this->formatTaskContext($tasks, $timezone);

        return "You are an automated task supervisor assistant. Review the following tasks due today:\n\n"
            . $taskContext . "\n\n"
            . 'Provide a brief, encouraging morning summary paragraph of what needs to be done.';
    }

    public function formatTaskContext(iterable $tasks, string $timezone): string
    {
        return collect($tasks)->map(function (Todo $task) use ($timezone) {
            // 3. Shift the stored UTC timestamp back to Africa/Kigali for presentation
            $due = $task->due_date ? Carbon::parse($task->due_date)->setTimezone($timezone)->format('Y-m-d H:i') : 'No due time';

            return "- Task: {$task->title} | Due: {$due}";
        })->implode("\n");
    }

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'date',
                description: 'The date to summarize (YYYY-MM-DD)',
                required: true,
            ),
        ];
    }
}
