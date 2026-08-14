<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\UserSignature;
use App\Services\UserSignatureNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserSignatureController extends Controller
{
    public function show(Request $request): View|JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isEligibleForSignatureEnrollment(), 403, 'Your account type is not eligible for academic signature enrollment.');

        $signature = $user->signature;

        if ($request->expectsJson()) {
            return response()->json([
                'has_signature' => $signature !== null,
                'signature' => $signature !== null ? [
                    'registered_at' => $signature->registered_at->toIso8601String(),
                    'preview_url' => route('signature.preview'),
                ] : null,
            ]);
        }

        return view('pages.settings.signature', [
            'signature' => $signature,
        ]);
    }

    public function preview(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->isEligibleForSignatureEnrollment(), 403);

        $signature = UserSignature::query()
            ->where('user_id', $user->id)
            ->first();

        if ($signature === null || ! Storage::disk($signature->storage_disk)->exists($signature->storage_path)) {
            abort(404, 'No enrolled signature found.');
        }

        return Storage::disk($signature->storage_disk)->response(
            $signature->storage_path,
            $signature->original_filename ?: 'signature.png',
            [
                'Content-Type' => 'image/png',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }

    public function store(Request $request, UserSignatureNormalizer $normalizer): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isEligibleForSignatureEnrollment(), 403, 'System Administrator accounts and inactive users cannot register an academic signature.');

        $request->validate([
            'signature' => ['required', 'file', 'max:2048', 'mimes:png,jpg,jpeg'],
        ]);

        $file = $request->file('signature');
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            throw ValidationException::withMessages([
                'signature' => ['The uploaded signature file is invalid.'],
            ]);
        }

        try {
            $rawContent = $file->get();
            $normalizedPng = $normalizer->normalize($rawContent);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'signature' => [$e->getMessage()],
            ]);
        }

        $disk = config('signatures.disk', 'local');
        $tempUuid = (string) Str::uuid();
        $finalUuid = (string) Str::uuid();
        $tempPath = "signatures/{$user->id}/temp_{$tempUuid}.png";
        $finalPath = "signatures/{$user->id}/{$finalUuid}.png";

        Storage::disk($disk)->put($tempPath, $normalizedPng);

        try {
            $oldPathToDelete = null;

            $signatureRecord = DB::transaction(function () use ($user, $disk, $tempPath, $finalPath, $normalizedPng, $file, &$oldPathToDelete): UserSignature {
                /** @var UserSignature|null $existing */
                $existing = UserSignature::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                $action = 'registered';

                if ($existing !== null) {
                    $oldPathToDelete = $existing->storage_path;
                    $action = 'replaced';
                }

                Storage::disk($disk)->move($tempPath, $finalPath);

                $record = UserSignature::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'storage_disk' => $disk,
                        'storage_path' => $finalPath,
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => 'image/png',
                        'file_size' => strlen($normalizedPng),
                        'content_sha256' => hash('sha256', $normalizedPng),
                        'registered_at' => now(),
                    ]
                );

                DB::table('signature_audits')->insert([
                    'user_signature_id' => $record->id,
                    'user_id' => $user->id,
                    'action' => $action,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                AuditLog::query()->create([
                    'user_id' => $user->id,
                    'actor_name' => $user->name,
                    'actor_email' => $user->email,
                    'event' => "user_signature.{$action}",
                    'auditable_type' => UserSignature::class,
                    'auditable_id' => $record->id,
                    'description' => "Digital signature specimen {$action} for {$user->name}.",
                    'subject_snapshot' => [
                        'action' => $action,
                        'content_sha256' => $record->content_sha256,
                    ],
                ]);

                return $record;
            });

            if ($oldPathToDelete !== null && Storage::disk($disk)->exists($oldPathToDelete)) {
                Storage::disk($disk)->delete($oldPathToDelete);
            }
        } catch (\Throwable $e) {
            if (Storage::disk($disk)->exists($tempPath)) {
                Storage::disk($disk)->delete($tempPath);
            }
            if (Storage::disk($disk)->exists($finalPath)) {
                Storage::disk($disk)->delete($finalPath);
            }
            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Digital signature registered successfully.',
                'signature' => [
                    'registered_at' => $signatureRecord->registered_at->toIso8601String(),
                    'preview_url' => route('signature.preview'),
                ],
            ]);
        }

        return back()->with('signature_success', 'Digital signature registered successfully.');
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isEligibleForSignatureEnrollment(), 403);

        $disk = config('signatures.disk', 'local');

        DB::transaction(function () use ($user, $disk): void {
            /** @var UserSignature|null $existing */
            $existing = UserSignature::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing === null) {
                return;
            }

            $path = $existing->storage_path;
            $signatureId = $existing->id;

            DB::table('signature_audits')->insert([
                'user_signature_id' => $signatureId,
                'user_id' => $user->id,
                'action' => 'removed',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $existing->delete();

            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            AuditLog::query()->create([
                'user_id' => $user->id,
                'actor_name' => $user->name,
                'actor_email' => $user->email,
                'event' => 'user_signature.removed',
                'auditable_type' => UserSignature::class,
                'auditable_id' => $signatureId,
                'description' => "Digital signature specimen removed by {$user->name}.",
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Digital signature removed successfully.',
            ]);
        }

        return back()->with('signature_success', 'Digital signature removed successfully.');
    }
}
