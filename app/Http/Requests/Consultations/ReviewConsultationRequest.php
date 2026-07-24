<?php

namespace App\Http\Requests\Consultations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReviewConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('consultations.manage-assigned') === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'review_notes' => ['bail', 'nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $notes = trim(strip_tags((string) $this->input('review_notes')));

        $this->merge([
            'review_notes' => $notes === '' ? null : $notes,
        ]);
    }

    protected function failedAuthorization(): void
    {
        $message = 'You do not have permission to manage consultation requests.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        throw new HttpResponseException(
            to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $message]),
        );
    }
}
