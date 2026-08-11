<?php

namespace App\Http\Requests\ResearchProgress;

use Illuminate\Foundation\Http\FormRequest;

class StartMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['override_order' => ['sometimes', 'boolean'], 'reason' => ['nullable', 'string', 'max:2000'], 'remarks' => ['nullable', 'string', 'max:4000']];
    }
}
