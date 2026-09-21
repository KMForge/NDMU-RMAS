<?php

namespace App\Http\Requests\Authentication;

use App\Modules\SystemSettings\Services\TurnstileSettings;
use App\Rules\TurnstileRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $programs = collect(config('academic.programs', []))->pluck('label')->all();

        $rules = [
            'student_id' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/', 'unique:users,student_id'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'ends_with:@ndmu.edu.ph', 'unique:users,email'],
            'program' => ['required', 'string', Rule::in($programs)],
            'year_level' => ['required', 'integer', 'between:1,5'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
        ];

        if (app(TurnstileSettings::class)->shouldValidate()) {
            $rules['cf-turnstile-response'] = ['required', new TurnstileRule];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $firstName = trim(strip_tags((string) $this->input('first_name')));
        $middleName = trim(strip_tags((string) $this->input('middle_name')));
        $lastName = trim(strip_tags((string) $this->input('last_name')));
        $suffix = trim(strip_tags((string) $this->input('suffix')));

        $name = trim(strip_tags((string) $this->input('name')));

        if ($firstName !== '' || $lastName !== '') {
            $name = trim("{$firstName} ".($middleName !== '' ? "{$middleName} " : '')."{$lastName}".($suffix !== '' ? " {$suffix}" : ''));
        } elseif ($name !== '') {
            $parts = preg_split('/\s+/', $name) ?: [];
            if (count($parts) === 1) {
                $firstName = $parts[0];
                $lastName = $parts[0];
            } elseif (count($parts) > 1) {
                $firstName = array_shift($parts);
                $lastName = array_pop($parts);
                $middleName = ! empty($parts) ? implode(' ', $parts) : '';
            }
        }

        $this->merge([
            'student_id' => mb_strtoupper(trim((string) $this->input('student_id'))),
            'first_name' => $firstName ?: null,
            'middle_name' => $middleName ?: null,
            'last_name' => $lastName ?: null,
            'suffix' => $suffix ?: null,
            'name' => $name,
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
