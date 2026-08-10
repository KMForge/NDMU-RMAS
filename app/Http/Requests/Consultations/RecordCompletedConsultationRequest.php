<?php

namespace App\Http\Requests\Consultations;

use App\Models\ConsultationRequest;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class RecordCompletedConsultationRequest extends FormRequest
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
            'consulted_at' => ['bail', 'nullable', 'date'],
            'duration_minutes' => ['bail', 'nullable', 'integer', 'min:15', 'max:480'],
            'discussion' => ['bail', 'required', 'string', 'min:5', 'max:5000'],
            'recommendations' => ['bail', 'nullable', 'string', 'max:5000'],
            'next_consultation_at' => ['bail', 'nullable', 'date'],
            'attendees' => ['bail', 'nullable', 'array'],
            'attendees.*' => ['bail', 'integer', 'exists:users,id'],
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();

        return [
            'consulted_at' => ! empty($validated['consulted_at']) ? CarbonImmutable::parse($validated['consulted_at']) : now(),
            'duration_minutes' => isset($validated['duration_minutes']) ? (int) $validated['duration_minutes'] : null,
            'discussion' => trim(strip_tags((string) $validated['discussion'])),
            'recommendations' => ! empty($validated['recommendations']) ? trim(strip_tags((string) $validated['recommendations'])) : null,
            'next_consultation_at' => ! empty($validated['next_consultation_at']) ? CarbonImmutable::parse($validated['next_consultation_at']) : null,
            'attendees' => array_map('intval', (array) ($validated['attendees'] ?? [])),
        ];
    }
}
