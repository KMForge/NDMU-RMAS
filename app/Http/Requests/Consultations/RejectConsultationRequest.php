<?php

namespace App\Http\Requests\Consultations;

use App\Models\ConsultationRequest;
use Illuminate\Foundation\Http\FormRequest;

class RejectConsultationRequest extends FormRequest
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
            'reason' => ['bail', 'required', 'string', 'min:3', 'max:2000'],
        ];
    }

    public function reason(): string
    {
        return trim(strip_tags((string) $this->input('reason')));
    }
}
