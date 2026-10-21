<?php

namespace App\Mcp\Prompts;

use App\Models\Todo;
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

        return Response::json(
            [
                "message" => "This is a daily summary for the date: {$request->get('date')}",
                "tasks" => Todo::where('completed', false)->where('created_at', '<=', $request->get('date'))->get(),
            ]
        );
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
