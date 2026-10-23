<?php

namespace App\Mcp\Tools;

use App\Models\Todo;
use App\Support\TodoDueDate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
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
        Create a new to-do task item with an optional due date and time. Include both the calendar date and time in due_date, for example 2026-10-24T14:30:00.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validation = Validator::make($request->all(), [
            'due_date' => ['nullable', 'date'],
        ]);

        if ($validation->fails()) {
            return Response::text('Use a valid due date and time, such as 2026-10-24T14:30:00.');
        }

        $todo = Todo::create([
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'due_date' => TodoDueDate::combine($request->get('due_date'), null),
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
            'due_date' => $schema->string()->format('date-time')->description('Date and optional time when the task is due, e.g. 2026-10-24T14:30:00. Use the task timezone.'),
            'completed' => $schema->boolean()->default(false)->description('Indicates whether the to-do task is completed.'),
        ];
    }
}
