<?php

namespace App\Modules\Signatures\Actions;

use App\Models\User;
use App\Models\UserSignature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RemoveUserSignature
{
    public function handle(
        UserSignature $signature,
        User $user,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        $disk = $signature->storage_disk;
        $path = $signature->storage_path;

        DB::transaction(function () use ($signature, $user, $ipAddress, $userAgent): void {
            $lockedSignature = UserSignature::query()
                ->whereKey($signature->getKey())
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            DB::table('signature_audits')->insert([
                'user_signature_id' => null,
                'user_id' => $user->getKey(),
                'action' => 'removed',
                'ip_address' => filter_var($ipAddress, FILTER_VALIDATE_IP) !== false ? $ipAddress : null,
                'user_agent' => mb_substr((string) $userAgent, 0, 1000) ?: null,
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lockedSignature->delete();
        }, 3);

        Storage::disk($disk)->delete($path);
    }
}
