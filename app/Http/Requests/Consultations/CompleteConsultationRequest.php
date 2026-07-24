<?php

namespace App\Http\Requests\Consultations;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompleteConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('consultations.manage-assigned') === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'consulted_at' => [
                'bail',
                'required',
                'date_format:Y-m-d\TH:i',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $consultedAt = CarbonImmutable::createFromFormat(
                        'Y-m-d\TH:i',
                        (string) $value,
                        config('ndmu-rmas.timezone'),
                    );

                    if ($consultedAt->isAfter(now(config('ndmu-rmas.timezone'))->addMinutes(5))) {
                        $fail('The consultation date and time cannot be in the future.');
                    }
                },
            ],
            'location' => ['bail', 'nullable', 'string', 'max:255'],
            'meeting_url' => ['bail', 'nullable', 'url:http,https', 'max:2048'],
            'discussion' => ['bail', 'required', 'string', 'min:10', 'max:10000'],
            'recommendations' => ['bail', 'nullable', 'string', 'max:10000'],
            'next_consultation_at' => [
                'bail',
                'nullable',
                'date_format:Y-m-d\TH:i',
                'after:consulted_at',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['location', 'discussion', 'recommendations'] as $field) {
            $value = trim(strip_tags((string) $this->input($field)));
            $this->merge([$field => $value === '' ? null : $value]);
        }

        $meetingUrl = trim((string) $this->input('meeting_url'));
        $nextConsultation = trim((string) $this->input('next_consultation_at'));

        $this->merge([
            'meeting_url' => $meetingUrl === '' ? null : $meetingUrl,
            'next_consultation_at' => $nextConsultation === '' ? null : $nextConsultation,
        ]);
    }

    protected function failedAuthorization(): void
    {
        $message = 'You do not have permission to record consultations.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        throw new HttpResponseException(
            to_route('adviser.dashboard', ['tab' => 'consultation'])
                ->withErrors(['consultation' => $message]),
        );
    }
}
