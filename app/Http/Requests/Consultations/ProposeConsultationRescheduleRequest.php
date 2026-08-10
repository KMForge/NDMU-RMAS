<?php

namespace App\Http\Requests\Consultations;

use App\Models\ConsultationRequest;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProposeConsultationRescheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $requestModel = $this->route('consultationRequest');

        return $requestModel instanceof ConsultationRequest
            && $this->user()?->can('adviserManage', $requestModel) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'proposed_start_at' => ['bail', 'required', 'date'],
            'duration_minutes' => ['bail', 'nullable', 'integer', Rule::in((array) config('consultations.allowed_durations', [30, 45, 60]))],
            'reason' => ['bail', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();

        return [
            'proposed_start_at' => CarbonImmutable::parse($validated['proposed_start_at']),
            'duration_minutes' => isset($validated['duration_minutes']) ? (int) $validated['duration_minutes'] : null,
            'reason' => ! empty($validated['reason']) ? trim(strip_tags((string) $validated['reason'])) : null,
        ];
    }
}
