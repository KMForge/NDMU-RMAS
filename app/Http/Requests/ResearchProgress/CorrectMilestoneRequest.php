<?php

namespace App\Http\Requests\ResearchProgress;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorrectMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'in_progress', 'not_applicable'])],
            'reason' => ['required', 'string', 'max:2000'], 'remarks' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
