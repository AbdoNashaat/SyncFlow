<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['owner_id' => $this->user->id]);
        $this->project->members()->attach($this->user->id, ['role' => 'owner']);
        $this->task = Task::factory()
            ->forProject($this->project)
            ->create(['created_by' => $this->user->id]);
    }

    private function authHeaders(): array
    {
        $token = $this->user->createToken('test-token')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function uploadPath(): string
    {
        return "/api/projects/{$this->project->id}/tasks/{$this->task->id}/attachments";
    }

    public function test_can_upload_single_file(): void
    {
        $file = UploadedFile::fake()->create('screenshot.png', 100, 'image/png');

        $response = $this->postJson($this->uploadPath(), [
            'attachments' => [$file],
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonCount(1, 'data');

        Storage::disk('local')->assertExists('attachments/' . $this->task->id . '/');
    }

    public function test_can_upload_multiple_files(): void
    {
        $files = [
            UploadedFile::fake()->create('photo1.jpg', 100, 'image/jpeg'),
            UploadedFile::fake()->create('doc.pdf', 500, 'application/pdf'),
        ];

        $response = $this->postJson($this->uploadPath(), [
            'attachments' => $files,
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data');
    }

    public function test_upload_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('script.exe', 100);

        $response = $this->postJson($this->uploadPath(), [
            'attachments' => [$file],
        ], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_upload_rejects_file_too_large(): void
    {
        $file = UploadedFile::fake()->create('large.pdf', 11264);

        $response = $this->postJson($this->uploadPath(), [
            'attachments' => [$file],
        ], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_upload_rejects_more_than_5_files(): void
    {
        $files = [];
        for ($i = 0; $i < 6; $i++) {
            $files[] = UploadedFile::fake()->create("photo{$i}.jpg", 100, 'image/jpeg');
        }

        $response = $this->postJson($this->uploadPath(), [
            'attachments' => $files,
        ], $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_non_member_cannot_upload(): void
    {
        $nonMember = User::factory()->create();
        $token = $nonMember->createToken('test-token')->plainTextToken;
        $file = UploadedFile::fake()->create('hack.png', 100, 'image/png');

        $response = $this->postJson($this->uploadPath(), [
            'attachments' => [$file],
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(403);
    }

    public function test_can_delete_attachment(): void
    {
        $file = UploadedFile::fake()->create('delete-me.png', 100, 'image/png');
        $upload = $this->postJson($this->uploadPath(), [
            'attachments' => [$file],
        ], $this->authHeaders());

        $attachmentId = $upload->json('data.0.id');

        $response = $this->deleteJson(
            "/api/tasks/{$this->task->id}/attachments/{$attachmentId}",
            [],
            $this->authHeaders()
        );

        $response->assertStatus(200)
            ->assertJson(['message' => 'Attachment deleted']);

        $this->assertDatabaseMissing('task_attachments', ['id' => $attachmentId]);
    }
}
