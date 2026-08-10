<?php

namespace App\Http\Requests\Consultations;

use App\Enums\ConsultationMode;
use App\Models\ConsultationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultationMeetingDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $consultationRequest = $this->route('consultationRequest');

        return $consultationRequest instanceof ConsultationRequest
            && $this->user()?->can('adviserManage', $consultationRequest) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'consultation_mode' => ['bail', 'required', Rule::enum(ConsultationMode::class)],
            'location' => ['bail', 'nullable', 'string', 'max:500'],
            'meeting_url' => ['bail', 'nullable', 'url:http,https', 'max:1000'],
        ];
    }

    /**
     * @return array{consultation_mode: string, location: ?string, meeting_url: ?string}
     */
    public function validatedData(): array
    {
        $validated = $this->validated();

        return [
            'consultation_mode' => (string) $validated['consultation_mode'],
            'location' => ! empty($validated['location']) ? trim(strip_tags((string) $validated['location'])) : null,
            'meeting_url' => ! empty($validated['meeting_url']) ? trim((string) $validated['meeting_url']) : null,
        ];
    }
}
