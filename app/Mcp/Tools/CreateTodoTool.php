<?php

namespace App\Mcp\Tools;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateTodoTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $name = 'create_todo';
    protected string $description = <<<'MARKDOWN'
        Create a new to-do task item with an optional due date.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(array $arguments): Response
    {
        Todo::create([
            'title' => $arguments['title'],
            'description' => $arguments['description'],
            'due_date' => $arguments['due_date'] ?? null,
            'completed' => $arguments['completed'] ?? false,
        ]);

        return Response::text('successfully created a new task.');
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The title of the to-do task.'),
            'description' => $schema->string()->description('A detailed description of the to-do task.'),
            'due_date' => $schema->string()->format('date')->description('The due date for the to-do task (YYYY-MM-DD).'),
            'completed' => $schema->boolean()->default(false)->description('Indicates whether the to-do task is completed.'),
        ];
    }
}
