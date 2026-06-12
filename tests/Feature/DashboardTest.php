<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['owner_id' => $this->user->id]);
        $this->project->members()->attach($this->user->id, ['role' => 'owner']);
    }

    private function authHeaders(): array
    {
        $token = $this->user->createToken('test-token')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_returns_dashboard_stats(): void
    {
        // Create some tasks with different statuses
        Task::factory()
            ->forProject($this->project)
            ->create(['assigned_user_id' => $this->user->id, 'status' => 'todo']);
        Task::factory()
            ->forProject($this->project)
            ->create(['assigned_user_id' => $this->user->id, 'status' => 'in_progress']);
        Task::factory()
            ->forProject($this->project)
            ->create(['assigned_user_id' => $this->user->id, 'status' => 'done']);

        $response = $this->getJson('/api/dashboard', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_projects',
                    'overdue_tasks',
                    'completed_this_week',
                    'by_status' => ['todo', 'in_progress', 'review', 'done'],
                    'recent_activity',
                ],
            ])
            ->assertJsonPath('data.total_projects', 1)
            ->assertJsonPath('data.by_status.todo', 1)
            ->assertJsonPath('data.by_status.in_progress', 1)
            ->assertJsonPath('data.by_status.done', 1);
    }

    public function test_counts_overdue_tasks(): void
    {
        Task::factory()
            ->forProject($this->project)
            ->create([
                'assigned_user_id' => $this->user->id,
                'due_date' => Carbon::yesterday(),
                'status' => 'in_progress',
            ]);
        Task::factory()
            ->forProject($this->project)
            ->create([
                'assigned_user_id' => $this->user->id,
                'due_date' => Carbon::yesterday(),
                'status' => 'done', // completed — not overdue
            ]);

        $response = $this->getJson('/api/dashboard', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('data.overdue_tasks', 1);
    }

    public function test_counts_completed_this_week(): void
    {
        Task::factory()
            ->forProject($this->project)
            ->create([
                'assigned_user_id' => $this->user->id,
                'status' => 'done',
                'updated_at' => Carbon::now(),
            ]);
        Task::factory()
            ->forProject($this->project)
            ->create([
                'assigned_user_id' => $this->user->id,
                'status' => 'done',
                'updated_at' => Carbon::now()->subWeek()->subDay(),
            ]);

        $response = $this->getJson('/api/dashboard', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('data.completed_this_week', 1);
    }

    public function test_includes_recent_activity(): void
    {
        Task::factory()
            ->forProject($this->project)
            ->count(3)
            ->create(['assigned_user_id' => $this->user->id]);

        $response = $this->getJson('/api/dashboard', $this->authHeaders());

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.recent_activity'));
    }

    public function test_only_counts_users_tasks_in_by_status(): void
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['owner_id' => $otherUser]);
        $otherProject->members()->attach($otherUser->id, ['role' => 'owner']);

        Task::factory()
            ->forProject($otherProject)
            ->create(['assigned_user_id' => $otherUser->id, 'status' => 'todo']);

        // User should only see their own context
        $response = $this->getJson('/api/dashboard', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('data.total_projects', 1);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(401);
    }
}
