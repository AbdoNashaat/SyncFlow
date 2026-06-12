<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{
    public function index(Project $project, Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', [$task]);

        if ($task->project_id !== $project->id) {
            abort(404, 'Task not found in this project');
        }

        $comments = $task->comments()
            ->with('user')
            ->oldest()
            ->paginate(20);

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', [$task]);

        if ($task->project_id !== $project->id) {
            return response()->json(['message' => 'Task not found in this project'], 404);
        }

        $comment = $task->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        $comment->load('user');

        return response()->json([
            'data' => new CommentResource($comment),
        ], 201);
    }

    public function destroy(TaskComment $comment): JsonResponse
    {
        $task = $comment->task;
        $project = $task->project;

        // Only comment author or project owner can delete
        if ($comment->user_id !== auth()->id() && $project->owner_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted',
        ]);
    }
}
