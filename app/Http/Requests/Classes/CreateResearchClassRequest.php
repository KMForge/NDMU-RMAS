<?php

namespace App\Http\Requests\Classes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateResearchClassRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return $this->user()?->can('classes.create') === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'creation_token' => ['bail', 'required', 'uuid'],
            'name' => ['bail', 'required', 'string', 'min:3', 'max:120'],
            'description' => ['bail', 'nullable', 'string', 'max:1000'],
            'join_code' => ['bail', 'nullable', 'string', 'min:6', 'max:16', 'regex:/^[A-Z0-9-]+$/'],
            'max_students' => ['bail', 'required', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeText($this->input('name')),
            'description' => $this->sanitizeText($this->input('description')),
            'join_code' => strtoupper(trim((string) $this->input('join_code'))),
        ]);
    }

    protected function failedAuthorization(): void
    {
        $message = 'You do not have permission to create classes.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        throw new HttpResponseException(
            to_route('adviser.dashboard', ['tab' => 'classes'])
                ->withErrors(['class' => $message]),
        );
    }

    private function sanitizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strip_tags((string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim($value ?? '');

        return $value === '' ? null : $value;
    }
}
