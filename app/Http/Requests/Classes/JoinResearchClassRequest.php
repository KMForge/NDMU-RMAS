<?php

namespace App\Http\Requests\Classes;

use App\Models\ResearchClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class JoinResearchClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('classes.join') === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'join_code' => ['bail', 'required', 'string', 'min:5', 'max:16', 'regex:/^[A-Z0-9]+$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'join_code' => ResearchClass::normalizeJoinCode((string) $this->input('join_code')),
        ]);
    }

    protected function failedAuthorization(): void
    {
        $message = 'You do not have permission to join classes.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        throw new HttpResponseException(
            to_route('student.dashboard', ['tab' => 'classes'])
                ->withErrors(['class' => $message]),
        );
    }
}
