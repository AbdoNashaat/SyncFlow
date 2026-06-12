<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskPositionRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskDetailResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends Controller
{
    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $query = $project->tasks()
            ->with(['assignedUser', 'creator'])
            ->withCount(['attachments', 'comments']);

        // Filters
        if ($status = request('status')) {
            $statuses = explode(',', $status);
            $query->whereIn('status', $statuses);
        }

        if ($priority = request('priority')) {
            $priorities = explode(',', $priority);
            $query->whereIn('priority', $priorities);
        }

        if ($assignedUser = request('assigned_user')) {
            $query->where('assigned_user_id', $assignedUser);
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('position')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $maxPosition = $project->tasks()
            ->where('status', $request->status ?? 'todo')
            ->max('position') ?? 0;

        $task = $project->tasks()->create([
            ...$request->validated(),
            'created_by' => auth()->id(),
            'position' => $maxPosition + 1,
        ]);

        $task->load(['assignedUser', 'creator']);

        return response()->json([
            'data' => new TaskResource($task),
        ], 201);
    }

    public function show(Project $project, Task $task): TaskDetailResource
    {
        $this->authorize('view', $task);

        $task->load([
            'assignedUser',
            'creator',
            'attachments.user',
            'comments.user',
        ]);

        return new TaskDetailResource($task);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task->update($request->validated());
        $task->load(['assignedUser', 'creator']);

        return new TaskResource($task);
    }

    public function destroy(Project $project, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json([
            'message' => 'Task deleted',
        ]);
    }

    public function updatePosition(UpdateTaskPositionRequest $request, Project $project, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task->update($request->validated());
        $task->load(['assignedUser', 'creator']);

        return new TaskResource($task);
    }
}
