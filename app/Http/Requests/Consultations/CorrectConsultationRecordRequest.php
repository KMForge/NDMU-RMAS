<?php

namespace App\Http\Requests\Consultations;

use App\Models\ConsultationRecord;
use Illuminate\Foundation\Http\FormRequest;

class CorrectConsultationRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('record');

        if (is_numeric($record)) {
            $record = ConsultationRecord::query()->with('request.researchClassGroup')->find($record);
        }

        if ($record instanceof ConsultationRecord && $record->request) {
            $record->request->loadMissing('researchClassGroup');

            return $this->user()?->can('adviserManage', $record->request) === true;
        }

        return false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'discussion' => ['bail', 'required', 'string', 'min:5', 'max:5000'],
            'recommendations' => ['bail', 'nullable', 'string', 'max:5000'],
            'correction_reason' => ['bail', 'required', 'string', 'min:5', 'max:2000'],
            'attendees' => ['bail', 'nullable', 'array'],
            'attendees.*' => ['bail', 'integer', 'exists:users,id'],
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();

        return [
            'discussion' => trim(strip_tags((string) $validated['discussion'])),
            'recommendations' => ! empty($validated['recommendations']) ? trim(strip_tags((string) $validated['recommendations'])) : null,
            'correction_reason' => trim(strip_tags((string) $validated['correction_reason'])),
            'attendees' => array_map('intval', (array) ($validated['attendees'] ?? [])),
        ];
    }
}
