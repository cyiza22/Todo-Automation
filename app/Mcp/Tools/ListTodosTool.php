<?php

namespace App\Mcp\Tools;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListTodosTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        fetch a list of all to-do tasks, optionally filtered by completion status or due date.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(array $arguments): Response
    {
        $todos = Todo::query()
            ->when(isset($arguments['completed']), function ($query) use ($arguments) {
                $query->where('completed', $arguments['completed']);
            })
            ->when(isset($arguments['due_date']), function ($query) use ($arguments) {
                $query->whereDate('due_date', $arguments['due_date']);
            })
            ->get();

        return Response::json($todos);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'completed' => $schema->boolean()->default(false)->description('Indicates whether to include completed tasks.'),
            'due_date' => $schema->string()->format('date')->description('The due date for the to-do task (YYYY-MM-DD).'),
        ];
    }
}
