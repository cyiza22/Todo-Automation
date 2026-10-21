<?php

namespace App\Mcp\Prompts;

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
        A description of what this prompt does.
    MARKDOWN;

    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {

        return Response::text("Please use the `list_todos` tool to check for tasks due on {\$arguments['date']}. " .
            "Then, write a bulleted summary grouped by status. Keep the tone professional.");
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, \Laravel\Mcp\Server\Prompts\Argument>
     */
    public function arguments(): array
    {
        return [
            Argument::make('date')
                ->description('The date to summarize (YYYY-MM-DD)')
                ->required(),
        ];
    }
}
