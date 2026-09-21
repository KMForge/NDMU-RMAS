<?php

namespace App\Rules;

use App\Modules\SystemSettings\Services\TurnstileSettings;
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
        if (! app(TurnstileSettings::class)->shouldValidate()) {
            return;
        }

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
            $data = [
                'secret' => $secretKey,
                'response' => $value,
            ];

            $clientIp = request()->ip();
            if (! empty($clientIp) && ! in_array($clientIp, ['127.0.0.1', '::1'], true)) {
                $data['remoteip'] = $clientIp;
            }

            $response = Http::asForm()
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $data);

            if (! $response->successful() || $response->json('success') !== true) {
                \Log::warning('Turnstile verification failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                $fail('Security verification failed. Please try again.');
            }
        } catch (\Throwable $e) {
            \Log::error('Turnstile connection error', [
                'message' => $e->getMessage(),
            ]);

            $fail('Unable to complete security verification. Please try again.');
        }
    }
}
