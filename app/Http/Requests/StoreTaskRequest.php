<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:todo,in_progress,review,done'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.after_or_equal' => 'Due date must be today or later.',
            'assigned_user_id.exists' => 'Assigned user does not exist.',
        ];
    }
}
