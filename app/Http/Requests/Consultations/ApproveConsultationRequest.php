<?php

namespace App\Http\Requests\Consultations;

use App\Enums\ConsultationMode;
use App\Models\ConsultationRequest;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveConsultationRequest extends FormRequest
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
            'confirmed_start_at' => ['bail', 'nullable', 'date'],
            'duration_minutes' => ['bail', 'nullable', 'integer', Rule::in((array) config('consultations.allowed_durations', [30, 45, 60]))],
            'consultation_mode' => ['bail', 'nullable', Rule::enum(ConsultationMode::class)],
            'location' => ['bail', 'nullable', 'string', 'max:500'],
            'meeting_url' => ['bail', 'nullable', 'url', 'max:1000'],
            'review_notes' => ['bail', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();

        return [
            'confirmed_start_at' => ! empty($validated['confirmed_start_at']) ? CarbonImmutable::parse($validated['confirmed_start_at']) : null,
            'duration_minutes' => isset($validated['duration_minutes']) ? (int) $validated['duration_minutes'] : null,
            'consultation_mode' => ! empty($validated['consultation_mode']) ? (string) $validated['consultation_mode'] : null,
            'location' => ! empty($validated['location']) ? trim(strip_tags((string) $validated['location'])) : null,
            'meeting_url' => ! empty($validated['meeting_url']) ? trim((string) $validated['meeting_url']) : null,
            'review_notes' => ! empty($validated['review_notes']) ? trim(strip_tags((string) $validated['review_notes'])) : null,
        ];
    }
}
