<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Translation\PotentiallyTranslatedString;

class TurnstileRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.turnstile.secret_key');

        // Gracefully bypass if Turnstile is not configured or in testing environment
        if (empty($secretKey) || app()->environment('testing')) {
            return;
        }

        if (empty($value) || ! is_string($value)) {
            $fail('Please complete the security verification.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->successful() || $response->json('success') !== true) {
                $fail('Security verification failed. Please try again.');
            }
        } catch (\Throwable) {
            $fail('Unable to complete security verification. Please try again.');
        }
    }
}
