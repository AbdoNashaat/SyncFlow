<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $editor;
    private User $viewer;
    private User $nonMember;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->editor = User::factory()->create();
        $this->viewer = User::factory()->create();
        $this->nonMember = User::factory()->create();

        $this->project = Project::factory()->create(['owner_id' => $this->owner->id]);
        $this->project->members()->attach($this->owner->id, ['role' => 'owner']);
        $this->project->members()->attach($this->editor->id, ['role' => 'editor']);
        $this->project->members()->attach($this->viewer->id, ['role' => 'viewer']);
    }

    private function authHeaders(User $user = null): array
    {
        $user ??= $this->owner;
        $token = $user->createToken('test-token')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function basePath(): string
    {
        return "/api/projects/{$this->project->id}/tasks";
    }

    public function test_can_list_tasks(): void
    {
        Task::factory(3)->forProject($this->project)->create();

        $response = $this->getJson($this->basePath(), $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_tasks_by_status(): void
    {
        Task::factory()->forProject($this->project)->create(['status' => 'todo']);
        Task::factory()->forProject($this->project)->create(['status' => 'done']);
        Task::factory()->forProject($this->project)->create(['status' => 'in_progress']);

        $response = $this->getJson($this->basePath() . '?status=todo,done', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_tasks_by_priority(): void
    {
        Task::factory()->forProject($this->project)->create(['priority' => 'high']);
        Task::factory()->forProject($this->project)->create(['priority' => 'low']);
        Task::factory()->forProject($this->project)->create(['priority' => 'medium']);

        $response = $this->getJson($this->basePath() . '?priority=high,medium', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_tasks(): void
    {
        Task::factory()->forProject($this->project)->create(['title' => 'Deploy to production']);
        Task::factory()->forProject($this->project)->create(['title' => 'Fix login bug']);

        $response = $this->getJson($this->basePath() . '?search=Deploy', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Deploy to production');
    }

    public function test_can_filter_by_assigned_user(): void
    {
        $assigned = User::factory()->create();
        Task::factory()->forProject($this->project)->create([
            'assigned_user_id' => $assigned->id,
        ]);
        Task::factory()->forProject($this->project)->create([
            'assigned_user_id' => $this->owner->id,
        ]);

        $response = $this->getJson(
            $this->basePath() . "?assigned_user={$assigned->id}",
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_owner_can_create_task(): void
    {
        $response = $this->postJson($this->basePath(), [
            'title' => 'New Task',
            'priority' => 'high',
        ], $this->authHeaders($this->owner));

        $response->assertStatus(201)
            ->assertJson([
                'data' => ['title' => 'New Task', 'priority' => 'high'],
            ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $this->project->id,
            'title' => 'New Task',
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_editor_can_create_task(): void
    {
        $response = $this->postJson($this->basePath(), [
            'title' => 'Editor Created Task',
        ], $this->authHeaders($this->editor));

        $response->assertStatus(201);
    }

    public function test_viewer_cannot_create_task(): void
    {
        $response = $this->postJson($this->basePath(), [
            'title' => 'Viewer Task',
        ], $this->authHeaders($this->viewer));

        $response->assertStatus(403);
    }

    public function test_non_member_cannot_list_tasks(): void
    {
        $response = $this->getJson($this->basePath(), $this->authHeaders($this->nonMember));

        $response->assertStatus(403);
    }

    public function test_can_view_task_detail(): void
    {
        $task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->owner->id]);

        $response = $this->getJson(
            "{$this->basePath()}/{$task->id}",
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id);
    }

    public function test_owner_can_update_task(): void
    {
        $task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->owner->id]);

        $response = $this->putJson("{$this->basePath()}/{$task->id}", [
            'title' => 'Updated Title',
            'status' => 'in_progress',
        ], $this->authHeaders($this->owner));

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_editor_can_update_task(): void
    {
        $task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->owner->id]);

        $response = $this->putJson("{$this->basePath()}/{$task->id}", [
            'status' => 'done',
        ], $this->authHeaders($this->editor));

        $response->assertStatus(200);
    }

    public function test_viewer_cannot_update_task(): void
    {
        $task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->owner->id]);

        $response = $this->putJson("{$this->basePath()}/{$task->id}", [
            'status' => 'done',
        ], $this->authHeaders($this->viewer));

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_task(): void
    {
        $task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->owner->id]);

        $response = $this->deleteJson(
            "{$this->basePath()}/{$task->id}",
            [],
            $this->authHeaders($this->owner)
        );

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted']);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_editor_can_delete_task(): void
    {
        $task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->owner->id]);

        $response = $this->deleteJson(
            "{$this->basePath()}/{$task->id}",
            [],
            $this->authHeaders($this->editor)
        );

        $response->assertStatus(200);
    }

    public function test_viewer_cannot_delete_task(): void
    {
        $task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->owner->id]);

        $response = $this->deleteJson(
            "{$this->basePath()}/{$task->id}",
            [],
            $this->authHeaders($this->viewer)
        );

        $response->assertStatus(403);
    }

    public function test_creator_can_delete_task_even_as_viewer(): void
    {
        $task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->viewer->id]);

        $response = $this->deleteJson(
            "{$this->basePath()}/{$task->id}",
            [],
            $this->authHeaders($this->viewer)
        );

        $response->assertStatus(200);
    }

    public function test_can_update_task_position(): void
    {
        $task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->owner->id, 'position' => 0]);

        $response = $this->patchJson(
            "{$this->basePath()}/{$task->id}/position",
            ['position' => 5, 'status' => 'in_progress'],
            $this->authHeaders($this->owner)
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.position', 5)
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_task_position_auto_increments(): void
    {
        $first = $this->postJson($this->basePath(), [
            'title' => 'First Task',
        ], $this->authHeaders($this->owner));

        $second = $this->postJson($this->basePath(), [
            'title' => 'Second Task',
        ], $this->authHeaders($this->owner));

        $first->assertJsonPath('data.position', 1);
        $second->assertJsonPath('data.position', 2);
    }

    public function test_unauthenticated_user_cannot_access_tasks(): void
    {
        $response = $this->getJson($this->basePath());
        $response->assertStatus(401);
    }
}
