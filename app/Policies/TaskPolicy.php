<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $this->isProjectMember($user, $project);
    }

    public function view(User $user, Task $task): bool
    {
        return $this->isProjectMember($user, $task->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->canEdit($user, $project);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->canEdit($user, $task->project);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->canEdit($user, $task->project)
            || $task->created_by === $user->id;
    }

    private function isProjectMember(User $user, Project $project): bool
    {
        return $user->id === $project->owner_id
            || $project->members()->where('user_id', $user->id)->exists();
    }

    private function canEdit(User $user, Project $project): bool
    {
        if ($user->id === $project->owner_id) {
            return true;
        }

        return $project->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'editor'])
            ->exists();
    }
}
