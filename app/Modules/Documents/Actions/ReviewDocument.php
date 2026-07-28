<?php

namespace App\Modules\Documents\Actions;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\DocumentReviewAudit;
use App\Models\ResearchProgressUpdate;
use App\Models\ResearchProposal;
use App\Models\RevisionRequest;
use App\Models\User;
use App\Modules\Documents\Exceptions\DocumentReviewException;
use App\Notifications\DocumentReviewStatusChanged;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReviewDocument
{
    public function handle(
        User $reviewer,
        Document $document,
        string $decision,
        ?string $notes,
        ?string $ipAddress = null,
    ): DocumentReview {
        try {
            return DB::transaction(function () use (
                $reviewer,
                $document,
                $decision,
                $notes,
                $ipAddress,
            ): DocumentReview {
                $lockedDocument = Document::query()
                    ->with('user:id,name,email')
                    ->whereKey($document->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (in_array($lockedDocument->status, [
                    DocumentStatus::Accepted,
                    DocumentStatus::Rejected,
                    DocumentStatus::RevisionRequested,
                ], true)) {
                    throw new DocumentReviewException(
                        'This document has already received a final review decision.',
                    );
                }

                $review = DocumentReview::query()->create([
                    'document_id' => $lockedDocument->getKey(),
                    'reviewer_id' => $reviewer->getKey(),
                    'decision' => $decision,
                    'review_notes' => $notes,
                    'reviewed_at' => now(),
                ]);

                $lockedDocument->update(['status' => DocumentStatus::from($decision)]);
                $this->syncReviewIntegrations($reviewer, $lockedDocument, $review, $notes, $ipAddress);

                return $review->load('reviewer:id,name');
            }, 3);
        } catch (DocumentReviewException $exception) {
            throw $exception;
        } catch (QueryException $exception) {
            report($exception);

            throw new DocumentReviewException(
                'The review decision could not be saved. Please try again.',
            );
        }
    }

    private function syncReviewIntegrations(
        User $reviewer,
        Document $document,
        DocumentReview $review,
        ?string $notes,
        ?string $ipAddress,
    ): void {
        $student = $document->user;
        $researchProjectId = $this->researchProjectIdFor($student);
        $safeNotes = Str::limit(trim(strip_tags((string) $notes)), 10000, '');
        $status = $this->proposalStatusFor($review->decision);

        if (Schema::hasTable('document_review_audits')) {
            DocumentReviewAudit::query()->create([
                'document_id' => $document->getKey(),
                'reviewer_id' => $reviewer->getKey(),
                'student_id' => $student->getKey(),
                'action' => 'document_reviewed',
                'decision' => $review->decision,
                'ip_address' => $this->safeIpAddress($ipAddress),
                'occurred_at' => now(),
                'metadata' => [
                    'original_filename' => $document->original_filename,
                    'file_type' => $document->file_type,
                ],
            ]);
        }

        if (Schema::hasTable('research_proposals')) {
            ResearchProposal::query()->updateOrCreate(
                ['document_id' => $document->getKey()],
                [
                    'research_project_id' => $researchProjectId,
                    'submitted_by' => $student->getKey(),
                    'reviewed_by' => $reviewer->getKey(),
                    'version' => $this->nextProposalVersion($researchProjectId, $document),
                    'title' => $document->original_filename,
                    'status' => $status,
                    'review_notes' => $safeNotes === '' ? null : $safeNotes,
                    'submitted_at' => $document->submitted_at,
                    'reviewed_at' => $review->reviewed_at,
                ],
            );
        }

        if ($review->decision === DocumentStatus::RevisionRequested->value && Schema::hasTable('revision_requests')) {
            RevisionRequest::query()->updateOrCreate(
                ['document_id' => $document->getKey(), 'status' => 'open'],
                [
                    'research_project_id' => $researchProjectId,
                    'requested_by' => $reviewer->getKey(),
                    'assigned_to' => $student->getKey(),
                    'title' => 'Revise '.$document->original_filename,
                    'instructions' => $safeNotes === '' ? 'Please revise the submitted document.' : $safeNotes,
                    'due_at' => now()->addWeek(),
                    'resolved_at' => null,
                ],
            );
        }

        if ($review->decision === DocumentStatus::Accepted->value && Schema::hasTable('research_progress_updates')) {
            $milestoneId = $this->milestoneIdFor($researchProjectId);

            ResearchProgressUpdate::query()->create([
                'research_project_id' => $researchProjectId,
                'milestone_id' => $milestoneId,
                'submitted_by' => $student->getKey(),
                'reviewed_by' => $reviewer->getKey(),
                'evidence_document_id' => $document->getKey(),
                'version' => $this->nextProgressVersion($researchProjectId, $milestoneId, $document),
                'status' => 'approved',
                'progress_percentage' => $this->progressPercentageFor($researchProjectId, $milestoneId),
                'summary' => 'Document approved: '.$document->original_filename,
                'feedback' => $safeNotes === '' ? null : $safeNotes,
                'submitted_at' => $document->submitted_at,
                'reviewed_at' => $review->reviewed_at,
            ]);
        }

        if (Schema::hasTable('notifications')) {
            $student->notify(new DocumentReviewStatusChanged($document, $review, $reviewer));
        }
    }

    private function proposalStatusFor(string $decision): string
    {
        return match ($decision) {
            DocumentStatus::Accepted->value => 'approved',
            DocumentStatus::RevisionRequested->value => 'revision_requested',
            DocumentStatus::Rejected->value => 'rejected',
            default => 'pending',
        };
    }

    private function researchProjectIdFor(User $student): ?int
    {
        if (! $this->tablesExist(['research_projects', 'student_profiles', 'research_group_members'])) {
            return null;
        }

        $studentProfile = DB::table('student_profiles')
            ->where('user_id', $student->getKey())
            ->first();

        $groupIds = $studentProfile === null
            ? collect()
            : DB::table('research_group_members')
                ->where('student_profile_id', $studentProfile->id)
                ->whereNull('left_at')
                ->pluck('research_group_id');

        return DB::table('research_projects')
            ->whereNull('archived_at')
            ->where(function ($query) use ($student, $groupIds): void {
                $query->where('created_by', $student->getKey());

                if ($groupIds->isNotEmpty()) {
                    $query->orWhereIn('research_group_id', $groupIds);
                }
            })
            ->latest('updated_at')
            ->value('id');
    }

    private function milestoneIdFor(?int $researchProjectId): ?int
    {
        if ($researchProjectId === null || ! $this->tablesExist(['research_projects', 'research_groups', 'research_milestones'])) {
            return null;
        }

        $project = DB::table('research_projects')->find($researchProjectId);

        if ($project === null) {
            return null;
        }

        $group = DB::table('research_groups')->find($project->research_group_id);

        if ($group === null) {
            return null;
        }

        return DB::table('research_milestones')
            ->where('program_id', $group->program_id)
            ->where('academic_term_id', $group->academic_term_id)
            ->orderBy('sequence')
            ->value('id');
    }

    private function nextProposalVersion(?int $researchProjectId, Document $document): int
    {
        if ($researchProjectId === null || ! Schema::hasTable('research_proposals')) {
            return 1;
        }

        $latestVersion = ResearchProposal::query()
            ->where('research_project_id', $researchProjectId)
            ->where('document_id', '!=', $document->getKey())
            ->max('version');

        return ((int) $latestVersion) + 1;
    }

    private function nextProgressVersion(?int $researchProjectId, ?int $milestoneId, Document $document): int
    {
        if ($researchProjectId === null || $milestoneId === null || ! Schema::hasTable('research_progress_updates')) {
            return 1;
        }

        $latestVersion = ResearchProgressUpdate::query()
            ->where('research_project_id', $researchProjectId)
            ->where('milestone_id', $milestoneId)
            ->where('evidence_document_id', '!=', $document->getKey())
            ->max('version');

        return ((int) $latestVersion) + 1;
    }

    private function progressPercentageFor(?int $researchProjectId, ?int $milestoneId): int
    {
        if ($researchProjectId === null || $milestoneId === null || ! $this->tablesExist(['research_projects', 'research_groups', 'research_milestones'])) {
            return 100;
        }

        $project = DB::table('research_projects')->find($researchProjectId);
        $group = $project === null ? null : DB::table('research_groups')->find($project->research_group_id);

        if ($group === null) {
            return 100;
        }

        $totalMilestones = DB::table('research_milestones')
            ->where('program_id', $group->program_id)
            ->where('academic_term_id', $group->academic_term_id)
            ->count();

        if ($totalMilestones < 1) {
            return 100;
        }

        $completedBeforeThisReview = ResearchProgressUpdate::query()
            ->where('research_project_id', $researchProjectId)
            ->where('status', 'approved')
            ->distinct('milestone_id')
            ->count('milestone_id');

        return (int) min(100, round((($completedBeforeThisReview + 1) / $totalMilestones) * 100));
    }

    private function safeIpAddress(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        return filter_var($ipAddress, FILTER_VALIDATE_IP) !== false ? $ipAddress : '0.0.0.0';
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function tablesExist(array $tables): bool
    {
        return collect($tables)->every(fn (string $table): bool => Schema::hasTable($table));
    }
}
