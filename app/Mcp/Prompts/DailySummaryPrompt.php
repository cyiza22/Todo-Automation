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
    /**
     * The prompt's description.
     */
    protected string $description = <<<'MARKDOWN'
       provides a daily summary of the tasks
    MARKDOWN;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        $timezone = config('app.task_timezone', config('app.timezone'));
        $date = Carbon::parse($request->get('date'), $timezone);
        $start = $date->copy()->startOfDay();
        $end = $start->copy()->addDay();

        $tasks = Todo::query()
            ->where('completed', false)
            ->where('due_date', '>=', $start->copy()->utc())
            ->where('due_date', '<', $end->copy()->utc())
            ->get();

        return Response::text($this->buildPrompt($tasks, $timezone));
    }

    /**
     * Build the instruction text used to summarize a collection of tasks.
     *
     * @param  iterable<Todo>  $tasks
     */
    public function buildPrompt(iterable $tasks, string $timezone): string
    {
        $taskContext = $this->formatTaskContext($tasks, $timezone);

        return "You are an automated task supervisor assistant. Review the following tasks due today:\n\n"
            . $taskContext . "\n\n"
            . 'Provide a brief, encouraging morning summary paragraph of what needs to be done.';
    }

    /**
     * @param  iterable<Todo>  $tasks
     */
    public function formatTaskContext(iterable $tasks, string $timezone): string
    {
        return collect($tasks)->map(function (Todo $task) use ($timezone) {
            $due = $task->due_date?->copy()->timezone($timezone)->format('Y-m-d H:i') ?? 'No due time';

            return "- Task: {$task->title} | Due: {$due}";
        })->implode("\n");
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
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
