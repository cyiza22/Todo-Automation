<?php

namespace App\Mcp\Tools;

use App\Models\Todo;
use App\Support\TodoDueDate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
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
    public function handle(Request $request): Response
    {
        $validation = Validator::make($request->all(), [
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable', 'date_format:H:i'],
        ]);

        if ($validation->fails()) {
            return Response::text('Use a valid due date and a 24-hour due time in HH:MM format, such as 18:05.');
        }

        $todo = Todo::find($request->get('id'));

        if (!$todo) {
            return Response::text('Task not found.');
        }

        $todo->update([
            'title' => $request->get('title', $todo->title),
            'description' => $request->get('description', $todo->description),
            'due_date' => TodoDueDate::combine($request->get('due_date'), $request->get('due_time'), $todo->due_date),
            'completed' => $request->get('completed', $todo->completed),
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
            'due_date' => $schema->string()->format('date')->description('Calendar date when the to-do task is due.'),
            'due_time' => $schema->string()->format('time')->description('Optional due time in 24-hour HH:MM format. Include this whenever a time is specified.'),
            'completed' => $schema->boolean()->default(false)->description('Indicates whether the to-do task is completed.'),
        ];
    }
}
