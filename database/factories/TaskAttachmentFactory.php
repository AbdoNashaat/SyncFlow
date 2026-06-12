<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskAttachmentFactory extends Factory
{
    protected $model = TaskAttachment::class;

    public function definition(): array
    {
        $ext = fake()->randomElement(['pdf', 'docx', 'xlsx', 'jpg', 'png', 'zip']);
        $filename = fake()->uuid() . '.' . $ext;

        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'filename' => $filename,
            'original_name' => fake()->word() . '.' . $ext,
            'mime_type' => fake()->randomElement([
                'application/pdf', 'application/msword',
                'image/jpeg', 'image/png', 'application/zip',
            ]),
            'size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            's3_key' => 'attachments/' . fake()->uuid() . '/' . $filename,
        ];
    }
}
