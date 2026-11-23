<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Carbon\Carbon;
use Gemini\Data\Content;
use Gemini\Data\FunctionDeclaration;
use Gemini\Data\FunctionResponse;
use Gemini\Data\Part;
use Gemini\Data\Schema;
use Gemini\Data\Tool;
use Gemini\Enums\DataType;
use Gemini\Enums\Role;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Throwable;

class AiAssistantController extends Controller
{
    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['sometimes', 'array', 'max:12'],
            'history.*.role' => ['required', 'in:user,model'],
            'history.*.text' => ['required', 'string', 'max:2000'],
        ]);

        if (!config('groq.api_key') && !config('gemini.api_key')) {
            return response()->json(['message' => 'AI assistant is not configured. Add GROQ_API_KEY or GEMINI_API_KEY to the server environment.'], 503);
        }

        $timezone = config('app.task_timezone', config('app.timezone'));
        $history = [];
        foreach ($data['history'] ?? [] as $turn) {
            $history[] = Content::parse($turn['text'], Role::from($turn['role']));
        }
        $instruction = new Content(parts: [new Part(
            text: "You are Daymark, an assistant that can manage this user's tasks. Use the provided task tools for any task lookup or change; never claim a change unless a tool confirms it. Dates and times use {$timezone}. Ask a concise follow-up when needed. Do not delete tasks; explain that the user can delete them in the task list."
        )]);

        try {
            if (config('groq.api_key')) {
                return $this->groqChat($data, $timezone);
            }

            $chat = Gemini::generativeModel(model: 'gemini-2.5-flash')
                ->withSystemInstruction($instruction)
                ->withTool(new Tool(functionDeclarations: $this->functionDeclarations()))
                ->startChat($history);
            $response = $chat->sendMessage($data['message']);
            $actions = [];

            // Allow a short sequence of tool calls, then ask Gemini to summarize their results.
            for ($round = 0; $round < 4; $round++) {
                $calls = collect($response->parts())->pluck('functionCall')->filter()->values();
                if ($calls->isEmpty()) {
                    break;
                }
                $functionResponses = [];
                foreach ($calls as $call) {
                    $result = $this->runTool($call->name, $call->args);
                    $actions[] = $result['action'];
                    $functionResponses[] = new Part(functionResponse: new FunctionResponse(
                            name: $call->name,
                            response: $result['response'],
                            id: $call->id,
                        ));
                }
                $response = $chat->sendMessage(new Content(
                        parts: $functionResponses,
                        role: Role::USER,
                    ));
            }

            return response()->json(['reply' => $response->text() ?: 'I could not form a response. Please try again.', 'actions' => $actions]);
        } catch (Throwable $exception) {
            Log::warning('AI assistant request failed.', ['exception' => $exception]);
            return response()->json(['message' => 'The AI assistant is temporarily unavailable. Please try again.'], 502);
        }
    }

    private function groqChat(array $data, string $timezone): JsonResponse
    {
        $messages = [[
            'role' => 'system',
            'content' => "You are Daymark, an assistant that can manage this user's tasks. Use the provided task tools for any task lookup or change; never claim a change unless a tool confirms it. Dates and times use {$timezone}. Ask a concise follow-up when needed. Do not delete tasks; explain that the user can delete them in the task list.",
        ]];
        foreach ($data['history'] ?? [] as $turn) {
            $messages[] = ['role' => $turn['role'] === 'model' ? 'assistant' : 'user', 'content' => $turn['text']];
        }
        $messages[] = ['role' => 'user', 'content' => $data['message']];
        $tools = [
            ['type' => 'function', 'function' => ['name' => 'list_tasks', 'description' => 'List tasks, optionally filter by completion status.', 'parameters' => ['type' => 'object', 'properties' => ['completed' => ['type' => 'boolean']], 'additionalProperties' => false]]],
            ['type' => 'function', 'function' => ['name' => 'create_task', 'description' => 'Create a task. Ask for clarification if the title or due date is ambiguous.', 'parameters' => ['type' => 'object', 'properties' => ['title' => ['type' => 'string'], 'description' => ['type' => 'string'], 'due_date' => ['type' => 'string']], 'required' => ['title'], 'additionalProperties' => false]]],
            ['type' => 'function', 'function' => ['name' => 'update_task', 'description' => 'Update a task by numeric ID. Only include fields the user wants changed.', 'parameters' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'title' => ['type' => 'string'], 'description' => ['type' => 'string'], 'due_date' => ['type' => 'string'], 'completed' => ['type' => 'boolean']], 'required' => ['id'], 'additionalProperties' => false]]],
        ];
        $actions = [];
        for ($round = 0; $round < 5; $round++) {
            $response = Http::withToken(config('groq.api_key'))
                ->acceptJson()->timeout((int) config('groq.request_timeout', 30))
                ->post(rtrim(config('groq.base_url'), '/').'/chat/completions', [
                    'model' => config('groq.model'), 'messages' => $messages, 'tools' => $tools, 'tool_choice' => 'auto',
                ])->throw()->json();
            $message = $response['choices'][0]['message'] ?? [];
            $calls = $message['tool_calls'] ?? [];
            if (!$calls) {
                return response()->json(['reply' => $message['content'] ?: 'I could not form a response. Please try again.', 'actions' => $actions]);
            }
            $messages[] = $message;
            foreach ($calls as $call) {
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                $result = $this->runTool($call['function']['name'], $args);
                $actions[] = $result['action'];
                $messages[] = ['role' => 'tool', 'tool_call_id' => $call['id'], 'content' => json_encode($result['response'])];
            }
        }

        return response()->json(['reply' => 'I completed the task actions, but could not prepare a final response. Please check the task list.', 'actions' => $actions]);
    }

    private function functionDeclarations(): array
    {
        $string = fn (string $description) => new Schema(type: DataType::STRING, description: $description);

        return [
            new FunctionDeclaration('list_tasks', 'List tasks, optionally filter by completion status.', new Schema(
                type: DataType::OBJECT,
                properties: ['completed' => new Schema(type: DataType::BOOLEAN, description: 'True for completed tasks, false for open tasks.')],
            )),
            new FunctionDeclaration('create_task', 'Create a task. Ask for clarification if the title or due date is ambiguous.', new Schema(
                type: DataType::OBJECT,
                properties: [
                    'title' => $string('Task title.'),
                    'description' => $string('Optional task details.'),
                    'due_date' => $string('Optional local date/time, ISO 8601 or YYYY-MM-DD HH:MM.'),
                ], required: ['title'],
            )),
            new FunctionDeclaration('update_task', 'Update an existing task by its numeric ID. Only include fields the user wants changed.', new Schema(
                type: DataType::OBJECT,
                properties: [
                    'id' => new Schema(type: DataType::INTEGER, description: 'Task ID.'),
                    'title' => $string('New title.'),
                    'description' => $string('New details.'),
                    'due_date' => $string('New local due date/time, or empty string to clear.'),
                    'completed' => new Schema(type: DataType::BOOLEAN, description: 'Completion status.'),
                ], required: ['id'],
            )),
        ];
    }

    private function runTool(string $name, array $args): array
    {
        if ($name === 'list_tasks') {
            $tasks = Todo::query()->when(array_key_exists('completed', $args), fn ($query) => $query->where('completed', (bool) $args['completed']))
                ->latest()->limit(100)->get();
            $items = $tasks->map(fn (Todo $todo) => $this->taskData($todo))->all();
            return ['response' => ['tasks' => $items], 'action' => 'Listed tasks'];
        }

        if ($name === 'create_task') {
            $title = trim((string) ($args['title'] ?? ''));
            if ($title === '' || mb_strlen($title) > 255) {
                return ['response' => ['error' => 'Task title is required and must be under 256 characters.'], 'action' => 'Create task rejected'];
            }
            $due = $this->parseDueDate($args['due_date'] ?? null);
            $todo = Todo::create([
                'title' => $title,
                'description' => $args['description'] ?? null,
                'due_date' => $due,
                'completed' => false,
            ]);
            return ['response' => ['task' => $this->taskData($todo), 'message' => 'Task created.'], 'action' => 'Created “'.$todo->title.'”'];
        }

        if ($name === 'update_task') {
            $todo = Todo::find($args['id'] ?? null);
            if (!$todo) {
                return ['response' => ['error' => 'Task not found.'], 'action' => 'Update task failed'];
            }
            $changes = [];
            foreach (['title', 'description', 'completed'] as $field) {
                if (array_key_exists($field, $args)) $changes[$field] = $args[$field];
            }
            if (array_key_exists('due_date', $args)) $changes['due_date'] = $this->parseDueDate($args['due_date'] ?: null);
            $todo->update($changes);
            return ['response' => ['task' => $this->taskData($todo->fresh()), 'message' => 'Task updated.'], 'action' => 'Updated “'.$todo->title.'”'];
        }

        return ['response' => ['error' => 'Unknown task operation.'], 'action' => 'Unknown task operation'];
    }

    private function parseDueDate(?string $value): ?string
    {
        if (!$value) return null;
        return Carbon::parse($value, config('app.task_timezone', config('app.timezone')))->utc()->format('Y-m-d H:i:s');
    }

    private function taskData(Todo $todo): array
    {
        $timezone = config('app.task_timezone', config('app.timezone'));
        return [
            'id' => $todo->id,
            'title' => $todo->title,
            'description' => $todo->description,
            'completed' => (bool) $todo->completed,
            'due_date' => $todo->due_date?->copy()->setTimezone($timezone)->format('Y-m-d H:i'),
        ];
    }
}
