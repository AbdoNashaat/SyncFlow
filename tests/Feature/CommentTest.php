<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherUser;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->project = Project::factory()->create(['owner_id' => $this->owner->id]);
        $this->project->members()->attach($this->owner->id, ['role' => 'owner']);
        $this->project->members()->attach($this->otherUser->id, ['role' => 'viewer']);
        $this->task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->owner->id]);
    }

    private function authHeaders(User $user = null): array
    {
        $user ??= $this->owner;
        $token = $user->createToken('test-token')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function commentsPath(): string
    {
        return "/api/projects/{$this->project->id}/tasks/{$this->task->id}/comments";
    }

    public function test_can_list_comments(): void
    {
        TaskComment::factory(3)->create([
            'task_id' => $this->task->id,
            'user_id' => $this->owner->id,
        ]);

        $response = $this->getJson($this->commentsPath(), $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_member_can_create_comment(): void
    {
        $response = $this->postJson($this->commentsPath(), [
            'content' => 'This is a comment',
        ], $this->authHeaders($this->otherUser));

        $response->assertStatus(201)
            ->assertJson([
                'data' => ['content' => 'This is a comment'],
            ]);
    }

    public function test_comment_has_user_data(): void
    {
        $response = $this->postJson($this->commentsPath(), [
            'content' => 'Who said this?',
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('data.user.id', $this->owner->id)
            ->assertJsonPath('data.user.name', $this->owner->name);
    }

    public function test_non_member_cannot_create_comment(): void
    {
        $nonMember = User::factory()->create();
        $token = $nonMember->createToken('test-token')->plainTextToken;

        $response = $this->postJson($this->commentsPath(), [
            'content' => 'Hack comment',
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(403);
    }

    public function test_comment_author_can_delete_own_comment(): void
    {
        $comment = TaskComment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->deleteJson(
            "/api/comments/{$comment->id}",
            [],
            $this->authHeaders($this->otherUser)
        );

        $response->assertStatus(200);
        $this->assertDatabaseMissing('task_comments', ['id' => $comment->id]);
    }

    public function test_project_owner_can_delete_any_comment(): void
    {
        $comment = TaskComment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->deleteJson(
            "/api/comments/{$comment->id}",
            [],
            $this->authHeaders($this->owner)
        );

        $response->assertStatus(200);
    }

    public function test_other_user_cannot_delete_someone_elses_comment(): void
    {
        $comment = TaskComment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->owner->id,
        ]);

        $anotherMember = User::factory()->create();
        $this->project->members()->attach($anotherMember->id, ['role' => 'viewer']);
        $token = $anotherMember->createToken('test-token')->plainTextToken;

        $response = $this->deleteJson(
            "/api/comments/{$comment->id}",
            [],
            ['Authorization' => 'Bearer ' . $token]
        );

        $response->assertStatus(403);
    }

    public function test_comments_are_ordered_oldest_first(): void
    {
        TaskComment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->owner->id,
            'created_at' => now()->subDay(),
        ]);
        TaskComment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->owner->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson($this->commentsPath(), $this->authHeaders());

        $response->assertStatus(200);
        $createdAts = collect($response->json('data'))->pluck('created_at');
        $sorted = $createdAts->values()->sort()->values();
        $this->assertEquals($sorted->toArray(), $createdAts->toArray());
    }
}
