<?php

namespace App\Http\Controllers;

use App\Http\Requests\Signatures\StoreUserSignatureRequest;
use App\Models\UserSignature;
use App\Modules\Signatures\Actions\RegisterUserSignature;
use App\Modules\Signatures\Actions\RemoveUserSignature;
use App\Modules\Signatures\Exceptions\SignatureRegistrationFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserSignatureController extends Controller
{
    public function show(Request $request): StreamedResponse
    {
        $signature = $this->signatureFor($request);
        Gate::authorize('view', $signature);

        $filesystem = Storage::disk($signature->storage_disk);

        abort_unless($filesystem->exists($signature->storage_path), 404);

        return response()->stream(function () use ($filesystem, $signature): void {
            $stream = $filesystem->readStream($signature->storage_path);

            abort_unless(is_resource($stream), 404);

            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $signature->mime_type,
            'Content-Disposition' => 'inline; filename="registered-signature.'.($signature->mime_type === 'image/png' ? 'png' : 'jpg').'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function store(
        StoreUserSignatureRequest $request,
        RegisterUserSignature $registerSignature,
    ): JsonResponse|RedirectResponse {
        try {
            $signature = $registerSignature->handle(
                $request->user(),
                $request->file('signature'),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (SignatureRegistrationFailed $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['signature' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Digital signature registered successfully.',
                'signature' => [
                    'registered_at' => $signature->registered_at?->toIso8601String(),
                    'preview_url' => route('signature.show'),
                ],
            ]);
        }

        return back()->with('signature_success', 'Digital signature registered successfully.');
    }

    public function destroy(
        Request $request,
        RemoveUserSignature $removeSignature,
    ): JsonResponse|RedirectResponse {
        $signature = $this->signatureFor($request);
        Gate::authorize('delete', $signature);

        $removeSignature->handle(
            $signature,
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Digital signature removed successfully.']);
        }

        return back()->with('signature_success', 'Digital signature removed successfully.');
    }

    private function signatureFor(Request $request): UserSignature
    {
        return UserSignature::query()
            ->where('user_id', $request->user()->getKey())
            ->firstOrFail();
    }
}
