<?php

namespace App\Mcp\Tools;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateTodoTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        A description of what this tool does.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(array $arguments): Response
    {
        $todo = Todo::find($arguments['id']);

        if (!$todo) {
            return Response::text('Task not found.');
        }

        $todo->update([
            'title' => $arguments['title'] ?? $todo->title,
            'description' => $arguments['description'] ?? $todo->description,
            'due_date' => $arguments['due_date'] ?? $todo->due_date,
            'completed' => $arguments['completed'] ?? $todo->completed,
        ]);

        return Response::json($todo);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()->description('The ID of the to-do task to update.'),
            'title' => $schema->string()->description('The title of the to-do task.'),
            'description' => $schema->string()->description('A detailed description of the to-do task.'),
            'due_date' => $schema->string()->format('date')->description('The due date for the to-do task (YYYY-MM-DD).'),
            'completed' => $schema->boolean()->default(false)->description('Indicates whether the to-do task is completed.'),
        ];
    }
}
