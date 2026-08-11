<?php

namespace App\Http\Requests\ResearchProgress;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMilestoneDueDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['due_at' => ['nullable', 'date'], 'reason' => ['nullable', 'string', 'max:2000']];
    }
}
