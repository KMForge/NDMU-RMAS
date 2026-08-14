<?php

namespace App\Http\Controllers;

use App\Models\OfficialFormVerification;
use App\Modules\OfficialForms\Services\OfficialFormVerificationService;
use App\Services\OfficialFormQrCodeGenerator;
use Illuminate\Http\Response;

class OfficialFormVerificationController extends Controller
{
    public function verify(
        string $reference,
        OfficialFormVerificationService $verifier,
        OfficialFormQrCodeGenerator $qrGenerator
    ): Response {
        /** @var OfficialFormVerification|null $verification */
        $verification = OfficialFormVerification::query()
            ->with(['version.instance.definition', 'version.signatures'])
            ->where('public_reference', $reference)
            ->first();

        if ($verification === null) {
            abort(404, 'Official Form verification record not found.');
        }

        $version = $verification->version;
        $instance = $version->instance;
        $evaluation = $verifier->evaluateVerification($version);

        $verifyUrl = route('official-forms.verify', ['reference' => $reference]);
        $qrSvgDataUri = null;

        try {
            $qrSvgDataUri = $qrGenerator->generateSvgDataUri($verifyUrl);
        } catch (\Throwable $e) {
            // QR generation failure falls back gracefully to URL text display
        }

        $content = view('pages.official-forms.verify', [
            'verification' => $verification,
            'version' => $version,
            'definition' => $instance->definition,
            'evaluation' => $evaluation,
            'verifyUrl' => $verifyUrl,
            'qrSvgDataUri' => $qrSvgDataUri,
        ])->render();

        return response($content, 200, [
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
            'Cache-Control' => 'no-store, private',
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
