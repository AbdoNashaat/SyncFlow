<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:todo,in_progress,review,done'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
