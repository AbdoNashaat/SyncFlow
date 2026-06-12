<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', [$task]);

        if ($task->project_id !== $project->id) {
            return response()->json(['message' => 'Task not found in this project'], 404);
        }

        $disk = Storage::disk(config('filesystems.default'));
        $uploaded = [];

        foreach ($request->file('attachments', []) as $file) {
            $uuid = Str::uuid();
            $extension = $file->getClientOriginalExtension();
            $filename = "{$uuid}.{$extension}";
            $s3Key = "attachments/{$task->id}/{$filename}";

            $disk->put($s3Key, file_get_contents($file->getRealPath()));

            $uploaded[] = $task->attachments()->create([
                'user_id' => auth()->id(),
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                's3_key' => $s3Key,
            ]);
        }

        return response()->json([
            'data' => AttachmentResource::collection($uploaded),
        ], 201);
    }

    public function destroy(Task $task, TaskAttachment $attachment): JsonResponse
    {
        $this->authorize('view', [$task]);

        if ($attachment->task_id !== $task->id) {
            return response()->json(['message' => 'Attachment not found'], 404);
        }

        $disk = Storage::disk(config('filesystems.default'));
        if ($disk->exists($attachment->s3_key)) {
            $disk->delete($attachment->s3_key);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted',
        ]);
    }
}
