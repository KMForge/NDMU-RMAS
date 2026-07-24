<?php

namespace App\Http\Requests\Consultations;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BookConsultationRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

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
            'request_token' => ['bail', 'required', 'uuid'],
            'preferred_at' => [
                'bail',
                'required',
                'date_format:Y-m-d\TH:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $preferredAt = $this->parsePreferredAt((string) $value);
                    $now = CarbonImmutable::now(config('ndmu-rmas.timezone', 'Asia/Manila'));

                    if ($preferredAt->lessThanOrEqualTo($now)) {
                        $fail('The preferred consultation schedule must be in the future.');
                    } elseif ($preferredAt->greaterThan($now->addMonths(3))) {
                        $fail('Consultations may only be requested up to three months ahead.');
                    }
                },
            ],
            'consultation_mode' => ['bail', 'required', Rule::in(['in_person', 'online'])],
            'agenda' => ['bail', 'required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'request_token.required' => 'The booking session is missing. Please reopen the form.',
            'request_token.uuid' => 'The booking session is invalid. Please reopen the form.',
            'preferred_at.required' => 'Please select your preferred consultation date and time.',
            'preferred_at.date_format' => 'The preferred consultation schedule is invalid.',
            'consultation_mode.required' => 'Please select a consultation mode.',
            'consultation_mode.in' => 'The selected consultation mode is invalid.',
            'agenda.required' => 'Please provide a consultation agenda.',
            'agenda.min' => 'The consultation agenda must contain at least 10 characters.',
            'agenda.max' => 'The consultation agenda must not exceed 2,000 characters.',
        ];
    }

    public function preferredAt(): CarbonImmutable
    {
        return $this->parsePreferredAt($this->validated('preferred_at'))->utc();
    }

    protected function prepareForValidation(): void
    {
        $agenda = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', strip_tags(
            (string) $this->input('agenda'),
        ));

        $this->merge([
            'consultation_mode' => strtolower(trim((string) $this->input('consultation_mode'))),
            'agenda' => trim($agenda ?? ''),
        ]);
    }

    protected function failedAuthorization(): void
    {
        $message = 'You do not have permission to book consultations.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        throw new HttpResponseException(
            to_route('student.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $message]),
        );
    }

    private function parsePreferredAt(string $value): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $value,
            (string) config('ndmu-rmas.timezone', 'Asia/Manila'),
        );
    }
}
