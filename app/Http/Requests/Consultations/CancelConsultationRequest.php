<?php

namespace App\Http\Requests\Consultations;

use App\Models\ConsultationRequest;
use Illuminate\Foundation\Http\FormRequest;

class CancelConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $requestModel = $this->route('consultationRequest');

        return $requestModel instanceof ConsultationRequest
            && $this->user()?->can('requesterManage', $requestModel) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['bail', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function reason(): ?string
    {
        $reason = trim(strip_tags((string) $this->input('reason', '')));

        return $reason !== '' ? $reason : null;
    }
}
