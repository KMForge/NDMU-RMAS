<?php

namespace App\Modules\Documents\Actions;

use App\Enums\DocumentStatus;
use App\Enums\RevisionStatus;
use App\Models\Document;
use App\Models\RevisionRequest;
use App\Models\RevisionRequestEvent;
use App\Models\User;
use App\Modules\Documents\Exceptions\DocumentUploadFailed;
use App\Modules\Documents\Exceptions\DuplicateDocumentSubmission;
use App\Modules\Documents\Support\DocumentFilenameSanitizer;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use App\Notifications\RevisionStatusChanged;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SubmitDocument
{
    public function __construct(
        private readonly DocumentFilenameSanitizer $filenameSanitizer,
        private readonly RecordDocumentUploadAttempt $audit,
    ) {}

    public function handle(
        User $user,
        UploadedFile $file,
        string $submissionToken,
        string $ipAddress,
        ?RevisionRequest $revisionRequest = null,
    ): Document {
        $lock = Cache::lock("document-upload:{$user->getKey()}:{$submissionToken}", 30);

        if (! $lock->get()) {
            throw new DuplicateDocumentSubmission;
        }

        try {
            if (Document::query()
                ->where('user_id', $user->getKey())
                ->where('submission_token', $submissionToken)
                ->exists()) {
                throw new DuplicateDocumentSubmission;
            }

            return $this->store(
                $user,
                $file,
                $submissionToken,
                $ipAddress,
                $revisionRequest,
            );
        } finally {
            $lock->release();
        }
    }

    private function store(
        User $user,
        UploadedFile $file,
        string $submissionToken,
        string $ipAddress,
        ?RevisionRequest $revisionRequest,
    ): Document {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedFilename = Str::uuid()->toString().".{$extension}";
        $disk = (string) config('ndmu-rmas.document.storage_disk', 'local');
        $directory = trim((string) config('ndmu-rmas.document.storage_directory', 'documents'), '/');
        $directory = "{$directory}/{$user->getKey()}/".now()->format('Y/m');
        $storedPath = null;

        try {
            $storedPath = Storage::disk($disk)->putFileAs(
                $directory,
                $file,
                $storedFilename,
                ['visibility' => 'private'],
            );

            if (! is_string($storedPath) || $storedPath === '') {
                throw new DocumentUploadFailed;
            }

            $realPath = $file->getRealPath();
            $hash = $realPath === false ? false : hash_file('sha256', $realPath);
            $size = $file->getSize();
            $mimeType = $file->getMimeType();

            if ($hash === false || $size === false || $mimeType === null) {
                throw new DocumentUploadFailed;
            }

            return DB::transaction(function () use (
                $user,
                $file,
                $submissionToken,
                $ipAddress,
                $storedFilename,
                $extension,
                $disk,
                $storedPath,
                $hash,
                $size,
                $mimeType,
                $revisionRequest,
            ): Document {
                $lockedRevision = null;

                if ($revisionRequest !== null) {
                    $lockedRevision = RevisionRequest::query()
                        ->whereKey($revisionRequest->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedRevision->assigned_to !== $user->getKey()) {
                        throw new RevisionWorkflowException(
                            'This revision request is not assigned to your account.',
                        );
                    }

                    if (! in_array($lockedRevision->status, [
                        RevisionStatus::Open,
                        RevisionStatus::InProgress,
                    ], true)) {
                        throw new RevisionWorkflowException(
                            'This revision request is not accepting another document.',
                        );
                    }
                }

                $document = Document::query()->create([
                    'user_id' => $user->getKey(),
                    'revision_request_id' => $lockedRevision?->getKey(),
                    'submission_token' => $submissionToken,
                    'original_filename' => $this->filenameSanitizer->sanitize(
                        $file->getClientOriginalName(),
                    ),
                    'stored_filename' => $storedFilename,
                    'file_type' => $extension,
                    'mime_type' => $mimeType,
                    'file_size' => $size,
                    'storage_disk' => $disk,
                    'storage_path' => $storedPath,
                    'content_sha256' => $hash,
                    'submitted_at' => now(),
                    'status' => DocumentStatus::Pending,
                ]);

                $this->audit->success($document, $user, $file, $ipAddress);

                if ($lockedRevision !== null) {
                    $from = $lockedRevision->status;
                    $lockedRevision->update([
                        'status' => RevisionStatus::Submitted,
                        'resolved_at' => null,
                    ]);

                    RevisionRequestEvent::query()->create([
                        'revision_request_id' => $lockedRevision->getKey(),
                        'actor_id' => $user->getKey(),
                        'document_id' => $document->getKey(),
                        'action' => 'submitted',
                        'from_status' => $from->value,
                        'to_status' => RevisionStatus::Submitted->value,
                        'ip_address' => filter_var($ipAddress, FILTER_VALIDATE_IP) !== false
                            ? $ipAddress
                            : null,
                        'metadata' => [
                            'original_filename' => $document->original_filename,
                            'file_type' => $document->file_type,
                            'file_size' => $document->file_size,
                        ],
                        'occurred_at' => now(),
                    ]);

                    $requester = User::query()->find($lockedRevision->requested_by);
                    $requester?->notify(
                        new RevisionStatusChanged($lockedRevision, $user, 'submitted'),
                    );
                }

                return $document;
            }, 3);
        } catch (DuplicateDocumentSubmission $exception) {
            $this->deleteStoredFile($disk, $storedPath);

            throw $exception;
        } catch (RevisionWorkflowException $exception) {
            $this->deleteStoredFile($disk, $storedPath);

            throw $exception;
        } catch (Throwable $exception) {
            $this->deleteStoredFile($disk, $storedPath);

            if ($exception instanceof DocumentUploadFailed) {
                throw $exception;
            }

            report($exception);

            throw new DocumentUploadFailed;
        }
    }

    private function deleteStoredFile(string $disk, ?string $path): void
    {
        if (is_string($path) && $path !== '') {
            Storage::disk($disk)->delete($path);
        }
    }
}
