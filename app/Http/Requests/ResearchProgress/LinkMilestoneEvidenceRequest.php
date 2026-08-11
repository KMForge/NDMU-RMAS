<?php

namespace App\Http\Requests\ResearchProgress;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkMilestoneEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evidence_type' => ['required', Rule::in(config('research-progress.evidence_types', []))],
            'evidence_id' => ['required', 'integer', 'min:1'], 'summary' => ['nullable', 'string', 'max:500'],
        ];
    }
}
