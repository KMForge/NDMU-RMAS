<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilePhotoController extends Controller
{
    public function show(Request $request): StreamedResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path === null
            || $user->profile_photo_disk === null
            || ! Storage::disk($user->profile_photo_disk)->exists($user->profile_photo_path)) {
            abort(404, 'No profile photo found.');
        }

        return Storage::disk($user->profile_photo_disk)->response(
            $user->profile_photo_path,
            'profile-photo.'.$this->extensionFor($user->profile_photo_mime_type),
            [
                'Content-Type' => $user->profile_photo_mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'inline',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'profile_photo' => [
                'required',
                'file',
                'max:2048',
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:min_width=80,min_height=80,max_width=4000,max_height=4000',
            ],
        ]);

        $file = $request->file('profile_photo');

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            throw ValidationException::withMessages([
                'profile_photo' => ['The uploaded profile photo is invalid.'],
            ]);
        }

        $mimeType = $file->getMimeType();
        $extension = $this->extensionFor($mimeType);

        if ($extension === 'bin') {
            throw ValidationException::withMessages([
                'profile_photo' => ['Only JPEG, PNG, and WebP profile photos are allowed.'],
            ]);
        }

        $disk = config('profile-photos.disk', 'local');
        $path = 'profile-photos/'.$request->user()->getKey().'/'.Str::uuid().'.'.$extension;
        $stored = Storage::disk($disk)->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        if ($stored === false) {
            throw new RuntimeException('The profile photo could not be stored.');
        }

        $oldDisk = null;
        $oldPath = null;

        try {
            DB::transaction(function () use ($request, $disk, $path, $mimeType, $file, &$oldDisk, &$oldPath): void {
                $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
                $oldDisk = $user->profile_photo_disk;
                $oldPath = $user->profile_photo_path;
                $action = $oldPath === null ? 'uploaded' : 'replaced';

                $user->forceFill([
                    'profile_photo_disk' => $disk,
                    'profile_photo_path' => $path,
                    'profile_photo_mime_type' => $mimeType,
                    'profile_photo_size' => $file->getSize(),
                    'profile_photo_updated_at' => now(),
                ])->save();

                AuditLog::query()->create([
                    'user_id' => $user->id,
                    'actor_name' => $user->name,
                    'actor_email' => $user->email,
                    'event' => "user_profile_photo.{$action}",
                    'auditable_type' => User::class,
                    'auditable_id' => $user->id,
                    'description' => "Profile photo {$action} by {$user->name}.",
                    'new_values' => [
                        'mime_type' => $mimeType,
                        'file_size' => $file->getSize(),
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        if ($oldDisk !== null && $oldPath !== null && ($oldDisk !== $disk || $oldPath !== $path)) {
            Storage::disk($oldDisk)->delete($oldPath);
        }

        $request->user()->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profile photo updated successfully.',
                'photo_url' => route('profile-photo.show', ['v' => $request->user()->profile_photo_updated_at?->timestamp]),
            ]);
        }

        return back()->with('profile_photo_success', 'Profile photo updated successfully.');
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $disk = null;
        $path = null;

        DB::transaction(function () use ($request, &$disk, &$path): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $disk = $user->profile_photo_disk;
            $path = $user->profile_photo_path;

            if ($path === null) {
                return;
            }

            $user->forceFill([
                'profile_photo_disk' => null,
                'profile_photo_path' => null,
                'profile_photo_mime_type' => null,
                'profile_photo_size' => null,
                'profile_photo_updated_at' => null,
            ])->save();

            AuditLog::query()->create([
                'user_id' => $user->id,
                'actor_name' => $user->name,
                'actor_email' => $user->email,
                'event' => 'user_profile_photo.removed',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'description' => "Profile photo removed by {$user->name}.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        if ($disk !== null && $path !== null) {
            Storage::disk($disk)->delete($path);
        }

        $request->user()->refresh();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Profile photo removed successfully.']);
        }

        return back()->with('profile_photo_success', 'Profile photo removed successfully.');
    }

    private function extensionFor(?string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }
}
