<?php

namespace App\Modules\Documents\Actions;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\RevisionStatus;
use App\Models\Document;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\RevisionRequest;
use App\Models\RevisionRequestEvent;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Documents\Exceptions\DocumentUploadFailed;
use App\Modules\Documents\Exceptions\DuplicateDocumentSubmission;
use App\Modules\Documents\Support\DocumentFilenameSanitizer;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use Illuminate\Auth\Access\AuthorizationException;
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
        private readonly WorkflowNotificationDispatcher $notifications,
        private readonly AuditLogWriter $auditLogs,
    ) {}

    public function handle(
        User $user,
        UploadedFile $file,
        string $submissionToken,
        string $ipAddress,
        ?RevisionRequest $revisionRequest = null,
        ?DocumentStage $documentStage = null,
    ): Document {
        $lock = Cache::lock("document-upload:{$user->getKey()}:{$submissionToken}", 30);

        if (! $lock->get()) {
            $this->audit->failure($user, $file, $ipAddress, 'This document submission has already been received.');

            throw new DuplicateDocumentSubmission;
        }

        try {
            if (Document::query()
                ->where('user_id', $user->getKey())
                ->where('submission_token', $submissionToken)
                ->exists()) {
                $this->audit->failure($user, $file, $ipAddress, 'This document submission has already been received.');

                throw new DuplicateDocumentSubmission;
            }

            return $this->store(
                $user,
                $file,
                $submissionToken,
                $ipAddress,
                $revisionRequest,
                $documentStage,
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
        ?DocumentStage $documentStage,
    ): Document {
        $groupMember = ResearchClassGroupMember::query()
            ->where('student_id', $user->getKey())
            ->whereHas('researchClassGroup', fn ($q) => $q->where('status', 'active'))
            ->whereHas('researchClassEnrollment', fn ($q) => $q->where('status', 'active'))
            ->with('researchClassGroup')
            ->first();

        if ($groupMember === null || $groupMember->researchClassGroup === null || ! $groupMember->researchClassGroup->isActive()) {
            $this->audit->failure($user, $file, $ipAddress, 'You do not belong to an active research group.');

            throw new AuthorizationException('You do not belong to an active research group.');
        }

        $group = $groupMember->researchClassGroup;

        if ($documentStage === null) {
            throw new DocumentUploadFailed('A valid document submission stage is required.');
        }

        if (! $group->isLeader($user)) {
            $this->audit->failure($user, $file, $ipAddress, 'Only your assigned Group Leader can submit research documents.', $group);

            throw new AuthorizationException('Only your assigned Group Leader can submit research documents.');
        }

        $realPath = $file->getRealPath();
        $hash = $realPath === false ? false : hash_file('sha256', $realPath);

        if ($hash !== false) {
            $exactDuplicateExists = Document::query()
                ->where('research_class_group_id', $group->getKey())
                ->where('is_current', true)
                ->where('content_sha256', $hash)
                ->exists();

            if ($exactDuplicateExists) {
                $this->audit->failure($user, $file, $ipAddress, 'This exact file has already been submitted for your research group.', $group);

                throw new DuplicateDocumentSubmission('This exact file has already been submitted for your research group.');
            }
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $storedFilename = Str::uuid()->toString().".{$extension}";
        $disk = (string) config('ndmu-rmas.document.storage_disk', 'local');
        $baseDir = trim((string) config('ndmu-rmas.document.storage_directory', 'documents'), '/');
        $directory = "{$baseDir}/groups/{$group->getKey()}/".now()->format('Y/m');
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

            $size = $file->getSize();
            $mimeType = $file->getMimeType();

            if ($hash === false || $size === false || $mimeType === null) {
                throw new DocumentUploadFailed;
            }

            return DB::transaction(function () use (
                $user,
                $group,
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
                $documentStage,
            ): Document {
                $lockedGroup = ResearchClassGroup::query()
                    ->whereKey($group->getKey())
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($lockedGroup === null || ! $lockedGroup->isLeader($user)) {
                    throw new AuthorizationException('Only your assigned Group Leader can submit research documents.');
                }

                $duplicateInsideTx = Document::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->where('is_current', true)
                    ->where('content_sha256', $hash)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicateInsideTx) {
                    throw new DuplicateDocumentSubmission('This exact file has already been submitted for your research group.');
                }

                $lockedRevision = null;

                if ($revisionRequest !== null) {
                    $lockedRevision = RevisionRequest::query()
                        ->whereKey($revisionRequest->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedRevision->research_class_group_id !== $lockedGroup->getKey()) {
                        throw new RevisionWorkflowException(
                            'This revision request does not belong to your research group.',
                        );
                    }

                    if ($lockedRevision->status !== RevisionStatus::InProgress) {
                        throw new RevisionWorkflowException(
                            'This revision request is not accepting another document.',
                        );
                    }

                    if ($lockedRevision->submitted_document_id !== null) {
                        throw new RevisionWorkflowException(
                            'A corrected document response has already been submitted for this revision cycle.',
                        );
                    }
                }

                $latestVersion = (int) Document::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->where('document_stage', $documentStage->value)
                    ->max('version_number');
                $nextVersion = max(1, $latestVersion + 1);

                Document::query()
                    ->where('research_class_group_id', $lockedGroup->getKey())
                    ->where('document_stage', $documentStage->value)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                $document = Document::query()->create([
                    'user_id' => $user->getKey(),
                    'research_class_group_id' => $lockedGroup->getKey(),
                    'revision_request_id' => $lockedRevision?->getKey(),
                    'submission_token' => $submissionToken,
                    'original_filename' => $this->filenameSanitizer->sanitize(
                        $file->getClientOriginalName(),
                    ),
                    'stored_filename' => $storedFilename,
                    'file_type' => $extension,
                    'mime_type' => $mimeType,
                    'document_stage' => $documentStage,
                    'version_number' => $nextVersion,
                    'is_current' => true,
                    'file_size' => $size,
                    'storage_disk' => $disk,
                    'storage_path' => $storedPath,
                    'content_sha256' => $hash,
                    'submitted_at' => now(),
                    'status' => $documentStage === DocumentStage::TitleProposal
                        ? DocumentStatus::Draft
                        : DocumentStatus::Pending,
                ]);

                $this->audit->success($document, $user, $file, $ipAddress, $lockedGroup);

                if ($lockedRevision !== null) {
                    $from = $lockedRevision->status;
                    $lockedRevision->update([
                        'submitted_document_id' => $document->getKey(),
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
                            'submitted_document_id' => $document->getKey(),
                        ],
                        'occurred_at' => now(),
                    ]);
                }

                $adviser = $lockedGroup->adviser_id !== null
                    ? User::query()->find($lockedGroup->adviser_id)
                    : null;

                if ($adviser !== null && $documentStage !== DocumentStage::TitleProposal) {
                    $isRevision = $lockedRevision !== null;
                    $this->notifications->send(
                        recipient: $adviser,
                        eventKey: $isRevision ? 'revision.document.submitted' : 'document.submitted',
                        title: $isRevision ? 'Revised document submitted' : 'Research document submitted',
                        message: "{$lockedGroup->name} submitted {$document->original_filename} for your attention.",
                        category: 'document',
                        routeName: 'adviser.dashboard',
                        routeParameters: ['tab' => $isRevision ? 'revisions' : 'docreview'],
                        sourceType: $isRevision ? RevisionRequest::class : Document::class,
                        sourceId: $isRevision ? $lockedRevision->getKey() : $document->getKey(),
                        actor: $user,
                        contextLabel: $lockedGroup->name,
                        actingAs: 'Thesis Adviser',
                        occurrence: (string) $document->version_number,
                    );
                }

                $this->auditLogs->write(
                    actor: $user,
                    event: $lockedRevision === null ? 'document.submitted' : 'revision.submitted',
                    description: $lockedRevision === null
                        ? 'A research document version was submitted.'
                        : 'A revised research document was submitted.',
                    requestContext: new AuditRequestContext(
                        filter_var($ipAddress, FILTER_VALIDATE_IP) !== false ? $ipAddress : null,
                        null,
                        null,
                    ),
                    auditable: $document,
                    subjectName: $document->original_filename,
                    newValues: [
                        'document_id' => $document->getKey(),
                        'research_class_group_id' => $lockedGroup->getKey(),
                        'stage' => $documentStage->value,
                        'version_number' => $document->version_number,
                        'file_type' => $document->file_type,
                        'file_size' => $document->file_size,
                        'status' => $document->status->value,
                    ],
                    actorContext: 'student-researcher',
                );

                return $document;
            }, 3);
        } catch (DuplicateDocumentSubmission $exception) {
            $this->deleteStoredFile($disk, $storedPath);

            throw $exception;
        } catch (AuthorizationException $exception) {
            $this->deleteStoredFile($disk, $storedPath);

            throw $exception;
        } catch (RevisionWorkflowException $exception) {
            $this->deleteStoredFile($disk, $storedPath);

            throw $exception;
        } catch (Throwable $exception) {
            $this->deleteStoredFile($disk, $storedPath);
            $this->audit->failure($user, $file, $ipAddress, 'The document could not be stored securely.', $group);

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
