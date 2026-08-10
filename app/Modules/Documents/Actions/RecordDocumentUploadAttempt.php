<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\DocumentUploadAudit;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\Documents\Support\DocumentFilenameSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RecordDocumentUploadAttempt
{
    public function __construct(
        private readonly DocumentFilenameSanitizer $filenameSanitizer,
    ) {}

    public function success(
        Document $document,
        User $user,
        UploadedFile $file,
        string $ipAddress,
        ?ResearchClassGroup $group = null,
    ): void {
        DocumentUploadAudit::query()->create([
            'document_id' => $document->getKey(),
            'user_id' => $user->getKey(),
            'research_class_group_id' => $group?->getKey() ?? $document->research_class_group_id,
            'original_filename' => $this->filenameSanitizer->sanitize($file->getClientOriginalName()),
            'ip_address' => $this->safeIpAddress($ipAddress),
            'attempted_at' => now(),
            'upload_status' => 'success',
            'failure_reason' => null,
        ]);
    }

    public function failure(
        ?User $user,
        ?UploadedFile $file,
        string $ipAddress,
        string $reason,
        ?ResearchClassGroup $group = null,
    ): void {
        try {
            DocumentUploadAudit::query()->create([
                'document_id' => null,
                'user_id' => $user?->getKey(),
                'research_class_group_id' => $group?->getKey(),
                'original_filename' => $this->filenameSanitizer->sanitize(
                    $file?->getClientOriginalName(),
                ),
                'ip_address' => $this->safeIpAddress($ipAddress),
                'attempted_at' => now(),
                'upload_status' => 'failed',
                'failure_reason' => Str::limit(strip_tags($reason), 500, ''),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to persist a failed document upload audit.', [
                'user_id' => $user?->getKey(),
                'group_id' => $group?->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }

    private function safeIpAddress(string $ipAddress): string
    {
        return filter_var($ipAddress, FILTER_VALIDATE_IP) !== false
            ? $ipAddress
            : '0.0.0.0';
    }
}
