<?php

namespace App\Http\Requests\Consultations;

use App\Enums\ConsultationMode;
use App\Enums\DocumentStage;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('consultations.request') === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'preferred_at' => ['bail', 'required', 'date'],
            'consultation_mode' => ['bail', 'required', Rule::enum(ConsultationMode::class)],
            'duration_minutes' => ['bail', 'nullable', 'integer', Rule::in((array) config('consultations.allowed_durations', [30, 45, 60]))],
            'agenda' => ['bail', 'required', 'string', 'min:10', 'max:2000'],
            'document_stage' => ['bail', 'nullable', Rule::enum(DocumentStage::class)],
            'document_id' => ['bail', 'nullable', 'integer', 'exists:documents,id'],
            'request_token' => ['bail', 'nullable', 'uuid'],
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();

        return [
            'preferred_at' => CarbonImmutable::parse($validated['preferred_at']),
            'consultation_mode' => (string) $validated['consultation_mode'],
            'duration_minutes' => isset($validated['duration_minutes']) ? (int) $validated['duration_minutes'] : config('consultations.default_duration', 60),
            'agenda' => trim(strip_tags((string) $validated['agenda'])),
            'document_stage' => ! empty($validated['document_stage']) ? (string) $validated['document_stage'] : null,
            'document_id' => ! empty($validated['document_id']) ? (int) $validated['document_id'] : null,
            'request_token' => ! empty($validated['request_token']) ? (string) $validated['request_token'] : null,
        ];
    }
}
