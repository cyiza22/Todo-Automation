<?php

namespace App\Mcp\Tools;

use App\Models\Todo;
use App\Support\TodoDueDate;
use Carbon\Carbon;
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
        // Adjust the validation to accommodate your custom format
        $validation = Validator::make($request->all(), [
            'due_date' => ['nullable', 'string'],
        ]);

        if ($validation->fails()) {
            return Response::text('Use a valid due date and time format, such as 30-11-2026 09:15.');
        }

        $todo = Todo::find($request->get('id'));

        if (!$todo) {
            return Response::text('Task not found.');
        }

        $inputDueDate = $request->get('due_date');
        $utcDateTimeString = TodoDueDate::combine($inputDueDate, null, $todo->due_date);

        $todo->update([
            'title' => $request->get('title', $todo->title),
            'description' => $request->get('description', $todo->description),
            'due_date' => $utcDateTimeString,
            'completed' => $request->get('completed', $todo->completed),
        ]);

        $timezone = trim(config('app.task_timezone', config('app.timezone', 'Africa/Kigali')));

        // Determine what to display for the due date text response
        $displayDueDate = $inputDueDate 
            ? trim($inputDueDate) 
            : ($todo->due_date ? Carbon::parse($todo->due_date)->setTimezone($timezone)->format('d-m-Y H:i') : null);

        return Response::json([
            'message' => 'Successfully updated the task.',
            'todo' => [
                'id' => $todo->id,
                'title' => $todo->title,
                'description' => $todo->description,
                'due_date' => $displayDueDate,
                'completed' => (bool) $todo->completed,
                'updated_at' => $todo->updated_at->setTimezone($timezone)->format('d-m-Y H:i'),
                'created_at' => $todo->created_at->setTimezone($timezone)->format('d-m-Y H:i'),
            ]
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
            'id' => $schema->string()->description('The ID of the to-do task to update.'),
            'title' => $schema->string()->description('The title of the to-do task.'),
            'description' => $schema->string()->description('A detailed description of the to-do task.'),
            'due_date' => $schema->string()->description('Updated date and time when the task is due, formatted strictly as DD-MM-YYYY HH:MM (e.g., 30-11-2026 09:15)'),
            'completed' => $schema->boolean()->default(false)->description('Indicates whether the to-do task is completed.'),
        ];
    }
}