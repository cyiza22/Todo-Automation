<?php

namespace App\Mcp\Tools;

use App\Models\Todo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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
    public function handle(Request $request): Response
    {
        $todo = Todo::create([
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'due_date' => $request->get('due_date'),
            'completed' => $request->get('completed', false),
        ]);

        return Response::json([
            'message' => 'Successfully created a new task.',
            'todo' => $todo,
        ]);
               
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
