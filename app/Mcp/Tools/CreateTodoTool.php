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

class CreateTodoTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $name = 'create_todo';
    protected string $description = <<<'MARKDOWN'
        Create a new to-do task item 
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $inputDueDate = $request->get('due_date');
        $utcDateTimeString = TodoDueDate::combine($inputDueDate, null);

        $todo = Todo::create([
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'due_date' => $utcDateTimeString,
            'completed' => $request->get('completed', false),
        ]);

        $timezone = trim(config('app.task_timezone', config('app.timezone', 'Africa/Kigali')));

        return Response::json([
            'message' => 'Successfully created a new task.',
            'todo' => [
                'title' => $todo->title,
                'description' => $todo->description,
                'due_date' => $inputDueDate ? trim($inputDueDate) : null,
                'completed' => $todo->completed,
                'updated_at' => $todo->updated_at->setTimezone($timezone)->format('d-m-Y H:i'),
                'created_at' => $todo->created_at->setTimezone($timezone)->format('d-m-Y H:i'),
                'id' => $todo->id
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
            'title' => $schema->string()->description('The title of the to-do task.'),
            'description' => $schema->string()->description('A detailed description of the to-do task.'),
            'due_date' => $schema->string()->description('Date and time when the task is due, formatted strictly as DD-MM-YYYY HH:MM (e.g., 30-11-2026 09:15)'),
            'completed' => $schema->boolean()->default(false)->description('Indicates whether the to-do task is completed.'),
        ];
    }

}
