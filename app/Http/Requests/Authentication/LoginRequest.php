<?php

namespace App\Http\Requests\Authentication;

use App\Rules\TurnstileRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ];

        if (config('services.turnstile.site_key') && ! app()->environment('testing')) {
            $rules['cf-turnstile-response'] = ['required', new TurnstileRule];
        }

        return $rules;
    }
}
