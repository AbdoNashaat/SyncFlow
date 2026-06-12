<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'max:5'],
            'attachments.*' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,pdf,docx,xlsx,zip'],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.max' => 'Maximum 5 files per upload.',
            'attachments.*.max' => 'Each file must be under 10MB.',
            'attachments.*.mimes' => 'Allowed types: jpg, png, gif, pdf, docx, xlsx, zip.',
        ];
    }
}
