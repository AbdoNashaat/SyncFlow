<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo admin user
        $admin = User::factory()->create([
            'name' => 'Abdelghafaar',
            'email' => 'abdelghafaar@syncflow.dev',
            'password' => bcrypt('password'),
            'avatar_url' => null,
            'timezone' => 'Africa/Cairo',
        ]);

        // 24 additional users (25 total)
        $users = User::factory(24)->create();
        $allUsers = $users->push($admin);

        // 10 projects, admin owns 4, others distributed
        $projects = collect();
        $projectNames = [
            'Q3 Product Launch', 'Mobile App Redesign', 'Data Migration',
            'Marketing Campaign', 'API Integration', 'Security Audit',
            'Documentation Overhaul', 'Customer Dashboard', 'Performance Optimization',
            'Team Onboarding',
        ];

        foreach ($projectNames as $i => $name) {
            $owner = $i < 4 ? $admin : $allUsers->random();
            $project = Project::factory()->create([
                'name' => $name,
                'owner_id' => $owner->id,
                'status' => $i < 8 ? 'active' : ($i < 9 ? 'completed' : 'archived'),
            ]);

            // Add 3-8 random members per project
            $memberCount = rand(3, 8);
            $members = $allUsers->where('id', '!=', $owner->id)->random(min($memberCount, $allUsers->count() - 1));
            $members->each(fn ($user) => $project->members()->attach($user->id, [
                'role' => fake()->randomElement(['editor', 'editor', 'viewer']),
            ]));
            // Add owner as member with owner role
            $project->members()->attach($owner->id, ['role' => 'owner']);

            $projects->push($project);
        }

        // 200 tasks distributed across projects
        $tasks = collect();
        $projects->each(function ($project) use ($allUsers, $admin, &$tasks) {
            $taskCount = rand(12, 28);
            $projectMembers = $project->members->pluck('id')->toArray();

            for ($i = 0; $i < $taskCount; $i++) {
                $assignee = fake()->boolean(70) ? $allUsers->random() : null;
                $creator = fake()->boolean(60) ? $admin : $allUsers->random();

                $task = Task::factory()->create([
                    'project_id' => $project->id,
                    'assigned_user_id' => $assignee?->id,
                    'created_by' => $creator->id,
                    'position' => $i,
                    'status' => fake()->randomElement([
                        'todo', 'todo', 'todo',
                        'in_progress', 'in_progress',
                        'review',
                        'done', 'done', 'done',
                    ]),
                ]);
                $tasks->push($task);

                // 40% of tasks have attachments
                if (fake()->boolean(40)) {
                    TaskAttachment::factory()->create([
                        'task_id' => $task->id,
                        'user_id' => $allUsers->random()->id,
                    ]);
                }

                // 60% of tasks have comments
                $commentCount = fake()->boolean(60) ? rand(1, 5) : 0;
                for ($c = 0; $c < $commentCount; $c++) {
                    TaskComment::factory()->create([
                        'task_id' => $task->id,
                        'user_id' => $allUsers->random()->id,
                    ]);
                }
            }
        });

        // Output stats
        echo "Seeded:\n";
        echo "  - " . User::count() . " users\n";
        echo "  - " . Project::count() . " projects\n";
        echo "  - " . Task::count() . " tasks\n";
        echo "  - " . TaskAttachment::count() . " attachments\n";
        echo "  - " . TaskComment::count() . " comments\n";
    }
}
