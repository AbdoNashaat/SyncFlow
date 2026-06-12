<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(6),
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => fake()->randomElement(['todo', 'todo', 'in_progress', 'review', 'done', 'done']),
            'priority' => fake()->randomElement(['low', 'medium', 'medium', 'high', 'urgent']),
            'due_date' => fake()->optional(0.6)->dateTimeBetween('-7 days', '+14 days'),
            'assigned_user_id' => User::factory(),
            'created_by' => User::factory(),
            'position' => fake()->numberBetween(0, 100),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state([
            'project_id' => $project->id,
        ]);
    }
}
