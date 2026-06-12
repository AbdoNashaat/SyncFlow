<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = auth()->id();

        // Projects the user owns or is a member of
        $projectIds = Project::query()
            ->where('owner_id', $userId)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->pluck('id');

        $taskQuery = Task::whereIn('project_id', $projectIds);
        $userTaskQuery = Task::whereIn('project_id', $projectIds)
            ->where('assigned_user_id', $userId);

        $totalProjects = $projectIds->count();

        $overdueTasks = (clone $userTaskQuery)
            ->where('due_date', '<', Carbon::today())
            ->where('status', '!=', 'done')
            ->count();

        $completedThisWeek = (clone $userTaskQuery)
            ->where('status', 'done')
            ->where('updated_at', '>=', Carbon::now()->startOfWeek())
            ->count();

        $byStatus = [
            'todo' => (clone $userTaskQuery)->where('status', 'todo')->count(),
            'in_progress' => (clone $userTaskQuery)->where('status', 'in_progress')->count(),
            'review' => (clone $userTaskQuery)->where('status', 'review')->count(),
            'done' => (clone $userTaskQuery)->where('status', 'done')->count(),
        ];

        // Recent activity: last 5 tasks updated
        $recentActivity = (clone $taskQuery)
            ->with(['project:id,name', 'assignedUser:id,name'])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn ($task) => [
                'type' => 'task_updated',
                'task_title' => $task->title,
                'task_id' => $task->id,
                'project_name' => $task->project->name,
                'assigned_user' => $task->assignedUser?->name,
                'time' => $task->updated_at->diffForHumans(),
            ]);

        return response()->json([
            'data' => [
                'total_projects' => $totalProjects,
                'overdue_tasks' => $overdueTasks,
                'completed_this_week' => $completedThisWeek,
                'by_status' => $byStatus,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }
}
