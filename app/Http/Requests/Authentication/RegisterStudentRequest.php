<?php

namespace App\Http\Requests\Authentication;

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

        return [
            'student_id' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/', 'unique:users,student_id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'ends_with:@ndmu.edu.ph', 'unique:users,email'],
            'program' => ['required', 'string', Rule::in($programs)],
            'year_level' => ['required', 'integer', 'between:1,5'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_id' => mb_strtoupper(trim((string) $this->input('student_id'))),
            'name' => trim(strip_tags((string) $this->input('name'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
