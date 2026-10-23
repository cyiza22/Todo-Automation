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
        Update a to-do task
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

        $todo = Todo::find($request->get('id'));

        if (!$todo) {
            return Response::text('Task not found.');
        }

        $todo->update([
            'title' => $request->get('title', $todo->title),
            'description' => $request->get('description', $todo->description),
            'due_date' => TodoDueDate::combine($request->get('due_date'), null, $todo->due_date),
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
            'due_date' => $schema->string()->format('date-time')->description('Date and optional time when the task is due, e.g. 2026-10-24T14:30:00'),
            'completed' => $schema->boolean()->default(false)->description('Indicates whether the to-do task is completed.'),
        ];
    }
}
