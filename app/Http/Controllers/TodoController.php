<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodoController extends Controller
{
    public function index(): JsonResponse
    {
        $timezone = config('app.task_timezone', config('app.timezone'));

        return response()->json(Todo::query()->latest()->get()->map(fn (Todo $todo) => [
            'id' => $todo->id,
            'title' => $todo->title,
            'description' => $todo->description,
            'completed' => $todo->completed,
            'due_date' => $todo->due_date?->copy()->setTimezone($timezone)->format('Y-m-d\TH:i'),
            'due_label' => $todo->due_date?->copy()->setTimezone($timezone)->format('M j, Y · g:i A'),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['due_date'] = $this->utcDate($data['due_date'] ?? null);
        $todo = Todo::create($data);

        return response()->json(['message' => 'Task created.', 'task' => $todo], 201);
    }

    public function update(Request $request, Todo $todo): JsonResponse
    {
        $data = $this->validated($request, true);
        if (array_key_exists('due_date', $data)) {
            $data['due_date'] = $this->utcDate($data['due_date']);
        }
        $todo->update($data);

        return response()->json(['message' => 'Task updated.']);
    }

    public function destroy(Todo $todo): JsonResponse
    {
        $todo->delete();

        return response()->json(['message' => 'Task deleted.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'completed' => ['sometimes', 'boolean'],
        ];

        return $request->validate($rules);
    }

    private function utcDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value, config('app.task_timezone', config('app.timezone')))
            ->utc()->format('Y-m-d H:i:s');
    }
}
