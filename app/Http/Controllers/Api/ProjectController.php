<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectDetailResource;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $projects = Project::query()
            ->where('owner_id', auth()->id())
            ->orWhereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
            ->withCount(['members', 'tasks'])
            ->with('owner')
            ->latest()
            ->paginate(15);

        return ProjectResource::collection($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::create([
            ...$request->validated(),
            'owner_id' => auth()->id(),
        ]);

        // Add creator as owner member
        $project->members()->attach(auth()->id(), ['role' => 'owner']);

        $project->load('owner');

        return response()->json([
            'data' => new ProjectResource($project),
        ], 201);
    }

    public function show(Project $project): ProjectDetailResource
    {
        $this->authorize('view', $project);

        $project->load(['owner', 'members']);

        return new ProjectDetailResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        $project->load('owner');

        return new ProjectResource($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted',
        ]);
    }
}
