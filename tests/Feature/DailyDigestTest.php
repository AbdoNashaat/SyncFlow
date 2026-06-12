<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DailyDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reports_no_overdue_tasks(): void
    {
        Artisan::call('app:send-daily-digest');

        $this->assertStringContainsString(
            'No overdue tasks found.',
            Artisan::output()
        );
    }

    public function test_command_reports_overdue_tasks_grouped_by_user(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        Task::factory()
            ->forProject($project)
            ->create([
                'title' => 'Overdue Task A',
                'assigned_user_id' => $user->id,
                'due_date' => Carbon::yesterday(),
                'status' => 'in_progress',
            ]);

        Artisan::call('app:send-daily-digest');

        $output = Artisan::output();

        $this->assertStringContainsString($user->name, $output);
        $this->assertStringContainsString('Overdue Task A', $output);
        $this->assertStringContainsString('1 overdue tasks:', $output);
    }

    public function test_command_ignores_completed_tasks(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        Task::factory()
            ->forProject($project)
            ->create([
                'title' => 'Completed but overdue',
                'assigned_user_id' => $user->id,
                'due_date' => Carbon::yesterday(),
                'status' => 'done', // completed — should be excluded
            ]);

        Artisan::call('app:send-daily-digest');

        $this->assertStringContainsString(
            'No overdue tasks found.',
            Artisan::output()
        );
    }

    public function test_command_logs_digest_to_laravel_log(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user->id]);

        Task::factory()
            ->forProject($project)
            ->create([
                'title' => 'Log Me',
                'assigned_user_id' => $user->id,
                'due_date' => Carbon::yesterday(),
                'status' => 'todo',
            ]);

        Log::shouldReceive('info')
            ->once()
            ->with(\Mockery::on(function ($message) {
                return str_contains($message, 'Log Me')
                    && str_contains($message, 'Daily Digest');
            }));

        Artisan::call('app:send-daily-digest');
    }

    public function test_command_groups_tasks_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $project = Project::factory()->create(['owner_id' => $user1->id]);

        Task::factory()
            ->forProject($project)
            ->create([
                'title' => 'User1 Task',
                'assigned_user_id' => $user1->id,
                'due_date' => Carbon::yesterday(),
                'status' => 'in_progress',
            ]);

        Task::factory()
            ->forProject($project)
            ->create([
                'title' => 'User2 Task',
                'assigned_user_id' => $user2->id,
                'due_date' => Carbon::yesterday(),
                'status' => 'in_progress',
            ]);

        Artisan::call('app:send-daily-digest');
        $output = Artisan::output();

        $this->assertStringContainsString($user1->name, $output);
        $this->assertStringContainsString($user2->name, $output);
        $this->assertStringContainsString('User1 Task', $output);
        $this->assertStringContainsString('User2 Task', $output);
    }

    public function test_command_returns_success_code(): void
    {
        $exitCode = Artisan::call('app:send-daily-digest');
        $this->assertEquals(0, $exitCode);
    }
}
