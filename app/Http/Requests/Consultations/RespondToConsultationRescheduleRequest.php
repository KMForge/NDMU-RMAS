<?php

namespace App\Http\Requests\Consultations;

use App\Models\ConsultationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondToConsultationRescheduleRequest extends FormRequest
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
            'action' => ['bail', 'required', 'string', Rule::in(['accept', 'decline'])],
        ];
    }
}
