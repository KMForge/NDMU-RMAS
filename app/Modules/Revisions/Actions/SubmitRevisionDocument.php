<?php

namespace App\Modules\Revisions\Actions;

use App\Enums\RevisionStatus;
use App\Models\Document;
use App\Models\RevisionRequest;
use App\Models\User;
use App\Modules\Documents\Actions\SubmitDocument;
use App\Modules\Revisions\Exceptions\RevisionWorkflowException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;

class SubmitRevisionDocument
{
    public function __construct(
        private readonly SubmitDocument $submitDocument,
    ) {}

    public function handle(
        User $user,
        UploadedFile $file,
        string $submissionToken,
        string $ipAddress,
        RevisionRequest $revisionRequest,
    ): Document {
        $cycle = RevisionRequest::query()
            ->with(['researchClassGroup', 'sourceDocument'])
            ->whereKey($revisionRequest->getKey())
            ->firstOrFail();

        $group = $cycle->researchClassGroup;

        if ($group === null || ! $group->isActive()) {
            throw new RevisionWorkflowException('The research group for this revision request is not active.');
        }

        if (! $group->isLeader($user)) {
            throw new AuthorizationException('Only your assigned Group Leader can submit revised research documents.');
        }

        if ($cycle->status !== RevisionStatus::InProgress) {
            throw new RevisionWorkflowException('This revision request is not accepting another document.');
        }

        if ($cycle->submitted_document_id !== null) {
            throw new RevisionWorkflowException('A corrected document response has already been submitted for this revision cycle.');
        }

        $sourceDoc = $cycle->sourceDocument;

        if ($sourceDoc === null || $sourceDoc->document_stage === null) {
            throw new RevisionWorkflowException('The source document stage could not be determined for this revision cycle.');
        }

        $derivedStage = $sourceDoc->document_stage;

        return $this->submitDocument->handle(
            $user,
            $file,
            $submissionToken,
            $ipAddress,
            $cycle,
            $derivedStage,
        );
    }
}
