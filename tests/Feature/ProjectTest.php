<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    private function authHeaders(): array
    {
        $token = $this->user->createToken('test-token')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_user_can_list_their_projects(): void
    {
        $owned = Project::factory()->create(['owner_id' => $this->user->id]);
        $member = Project::factory()->create(['owner_id' => $this->otherUser->id]);
        $member->members()->attach($this->user->id, ['role' => 'viewer']);

        // A project they don't belong to
        Project::factory()->create(['owner_id' => $this->otherUser->id]);

        $response = $this->getJson('/api/projects', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_project(): void
    {
        $response = $this->postJson('/api/projects', [
            'name' => 'New Project',
            'description' => 'A test project',
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJson([
                'data' => ['name' => 'New Project'],
            ]);

        $this->assertDatabaseHas('projects', ['name' => 'New Project']);
        // Creator should be a member with owner role
        $this->assertDatabaseHas('project_members', [
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);
    }

    public function test_user_can_view_project_they_own(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->user->id]);
        $project->members()->attach($this->user->id, ['role' => 'owner']);

        $response = $this->getJson("/api/projects/{$project->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['id' => $project->id, 'name' => $project->name],
            ]);
    }

    public function test_user_can_view_project_they_are_member_of(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->otherUser->id]);
        $project->members()->attach($this->user->id, ['role' => 'viewer']);

        $response = $this->getJson("/api/projects/{$project->id}", $this->authHeaders());

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_project_they_are_not_member_of(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->otherUser->id]);

        $response = $this->getJson("/api/projects/{$project->id}", $this->authHeaders());

        $response->assertStatus(403);
    }

    public function test_owner_can_update_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->putJson("/api/projects/{$project->id}", [
            'name' => 'Updated Name',
        ], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['name' => 'Updated Name'],
            ]);
    }

    public function test_editor_can_update_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->otherUser->id]);
        $project->members()->attach($this->user->id, ['role' => 'editor']);

        $response = $this->putJson("/api/projects/{$project->id}", [
            'name' => 'Updated By Editor',
        ], $this->authHeaders());

        $response->assertStatus(200);
    }

    public function test_viewer_cannot_update_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->otherUser->id]);
        $project->members()->attach($this->user->id, ['role' => 'viewer']);

        $response = $this->putJson("/api/projects/{$project->id}", [
            'name' => 'Should Not Update',
        ], $this->authHeaders());

        $response->assertStatus(403);
    }

    public function test_owner_can_delete_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->deleteJson("/api/projects/{$project->id}", [], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson(['message' => 'Project deleted']);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_non_owner_cannot_delete_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->otherUser->id]);
        $project->members()->attach($this->user->id, ['role' => 'editor']);

        $response = $this->deleteJson("/api/projects/{$project->id}", [], $this->authHeaders());

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_projects(): void
    {
        $response = $this->getJson('/api/projects');
        $response->assertStatus(401);
    }
}
