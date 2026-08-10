<?php

namespace App\Http\Requests\Consultations;

use App\Models\ConsultationRequest;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    public function validatedData(): array
    {
        $validated = $this->validated();

        return [
            'proposed_start_at' => CarbonImmutable::parse($validated['proposed_start_at']),
        ];
    }
}
