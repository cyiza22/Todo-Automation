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
        Create a new to-do task item with an optional due date and time. Supply due_time separately when a time is specified.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validation = Validator::make($request->all(), [
            'due_date' => ['nullable', 'date', 'required_with:due_time'],
            'due_time' => ['nullable', 'date_format:H:i'],
        ]);

        if ($validation->fails()) {
            return Response::text('Use a valid due date and a 24-hour due time in HH:MM format, such as 18:05.');
        }

        $todo = Todo::create([
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'due_date' => TodoDueDate::combine($request->get('due_date'), $request->get('due_time')),
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
            'due_date' => $schema->string()->format('date')->description('Calendar date when the to-do task is due.'),
            'due_time' => $schema->string()->format('time')->description('Optional due time in 24-hour HH:MM format. Include this whenever a time is specified.'),
            'completed' => $schema->boolean()->default(false)->description('Indicates whether the to-do task is completed.'),
        ];
    }
}
