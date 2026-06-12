<?php

namespace Tests\Feature\Phase1\Functional;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseRelationshipTest extends TestCase
{
    // This trait ensures the database resets after every test
    use RefreshDatabase;

    public function test_deleting_project_cascades_to_tasks(): void
    {
        // 1. Create a project with 5 associated tasks
        $project = Project::factory()
            ->has(Task::factory()->count(5))
            ->create();
        
        // 2. Verify they exist
        $this->assertDatabaseCount('tasks', 5);
        
        // 3. Delete the project
        $project->delete();
        
        // 4. Assert the tasks were deleted via database cascade
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_user_project_member_pivot_works(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        // Attach user to project as an editor
        $project->members()->attach($user->id, ['role' => 'editor']);

        // Assert the relationship exists and the pivot data is correct
        $this->assertTrue($project->members->contains($user));
        $this->assertEquals('editor', $project->members->first()->pivot->role);
    }
}