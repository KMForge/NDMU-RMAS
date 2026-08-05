<?php

namespace App\Modules\Signatures\Actions;

use App\Models\User;
use App\Models\UserSignature;
use App\Modules\Signatures\Exceptions\SignatureRegistrationFailed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RegisterUserSignature
{
    public function handle(
        User $user,
        UploadedFile $file,
        ?string $ipAddress,
        ?string $userAgent,
    ): UserSignature {
        $disk = (string) config('ndmu-rmas.signature.storage_disk', 'local');
        $directory = trim((string) config('ndmu-rmas.signature.storage_directory', 'signatures'), '/');
        $mimeType = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        $extension = $mimeType === 'image/png' ? 'png' : 'jpg';
        $storedFilename = Str::uuid().'.'.$extension;
        $storedPath = Storage::disk($disk)->putFileAs(
            $directory.'/'.$user->getKey(),
            $file,
            $storedFilename,
            ['visibility' => 'private'],
        );

        if (! is_string($storedPath) || $storedPath === '') {
            throw new SignatureRegistrationFailed('The signature image could not be stored.');
        }

        $previousDisk = null;
        $previousPath = null;

        try {
            $signature = DB::transaction(function () use (
                $user,
                $file,
                $disk,
                $storedPath,
                $mimeType,
                $ipAddress,
                $userAgent,
                &$previousDisk,
                &$previousPath,
            ): UserSignature {
                User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

                $existing = UserSignature::query()
                    ->where('user_id', $user->getKey())
                    ->lockForUpdate()
                    ->first();

                $previousDisk = $existing?->storage_disk;
                $previousPath = $existing?->storage_path;

                $signature = UserSignature::query()->updateOrCreate(
                    ['user_id' => $user->getKey()],
                    [
                        'storage_disk' => $disk,
                        'storage_path' => $storedPath,
                        'original_filename' => $this->safeOriginalFilename($file),
                        'mime_type' => $mimeType,
                        'file_size' => (int) $file->getSize(),
                        'content_sha256' => hash_file('sha256', $file->getRealPath()),
                        'registered_at' => now(),
                    ],
                );

                DB::table('signature_audits')->insert([
                    'user_signature_id' => $signature->getKey(),
                    'user_id' => $user->getKey(),
                    'action' => $existing === null ? 'registered' : 'replaced',
                    'ip_address' => $this->safeIpAddress($ipAddress),
                    'user_agent' => mb_substr((string) $userAgent, 0, 1000) ?: null,
                    'occurred_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $signature;
            }, 3);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storedPath);
            report($exception);

            throw new SignatureRegistrationFailed('The signature could not be registered.');
        }

        if (is_string($previousDisk) && is_string($previousPath) && $previousPath !== $storedPath) {
            Storage::disk($previousDisk)->delete($previousPath);
        }

        return $signature;
    }

    private function safeOriginalFilename(UploadedFile $file): string
    {
        $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $filename = preg_replace('/[^A-Za-z0-9._ -]/u', '_', $filename) ?? 'signature';

        return mb_substr(trim($filename, '. '), 0, 255) ?: 'signature';
    }

    private function safeIpAddress(?string $ipAddress): ?string
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP) !== false ? $ipAddress : null;
    }
}
