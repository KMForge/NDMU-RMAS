<?php

namespace App\Modules\Documents\Support;

use App\Models\Defense;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DocumentRepositoryAccess
{
    public function __construct(
        private readonly DocumentGroupAccess $groupAccess,
        private readonly DocumentReviewerAccess $reviewerAccess,
    ) {}

    public function canAccess(User $user, Document $document): bool
    {
        if (! $user->can('documents.download')) {
            return false;
        }

        if ($user->can('documents.download-any')) {
            return true;
        }

        if ($document->research_class_group_id === null) {
            return $user->getKey() === $document->user_id
                || $this->reviewerAccess->canReview($user, $document);
        }

        if ($this->groupAccess->isActiveMember($user, $document)
            || $this->groupAccess->isHistoricalMember($user, $document)) {
            return true;
        }

        if ($this->isActivePanelistForDocumentStage($user, $document)) {
            return true;
        }

        return Document::query()
            ->whereKey($document->getKey())
            ->where(function (Builder $query) use ($user): void {
                $query->whereHas('researchClassGroup', fn (Builder $group) => $group
                    ->where('adviser_id', $user->getKey())
                    ->where('status', 'active')
                    ->whereNull('disbanded_at'))
                    ->orWhereHas('researchClassGroup.researchClass', fn (Builder $class) => $class
                        ->where('facilitator_id', $user->getKey()));
            })
            ->exists();
    }

    /** @return Builder<Document> */
    public function scopeFor(Builder $query, User $user): Builder
    {
        if (! $user->can('documents.download')) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('documents.download-any')) {
            return $query;
        }

        return $query->where(function (Builder $access) use ($user): void {
            $access->where(function (Builder $modern) use ($user): void {
                $modern->whereNotNull('documents.research_class_group_id')
                    ->where(function (Builder $groups) use ($user): void {
                        $groups->whereHas('researchClassGroup.members', fn (Builder $members) => $members
                            ->where('student_id', $user->getKey())
                            ->whereHas('researchClassEnrollment', fn (Builder $enrollment) => $enrollment
                                ->where('status', 'active'))
                            ->whereHas('researchClassGroup', fn (Builder $group) => $group
                                ->where('status', 'active')
                                ->whereNull('disbanded_at')))
                            ->orWhereHas('researchClassGroup.memberHistories', fn (Builder $history) => $history
                                ->where('student_id', $user->getKey())
                                ->whereHas('group', fn (Builder $group) => $group
                                    ->where('status', 'disbanded')
                                    ->whereNotNull('disbanded_at')))
                            ->orWhereHas('researchClassGroup', fn (Builder $group) => $group
                                ->where('adviser_id', $user->getKey())
                                ->where('status', 'active')
                                ->whereNull('disbanded_at'))
                            ->orWhereHas('researchClassGroup.researchClass', fn (Builder $class) => $class
                                ->where('facilitator_id', $user->getKey()))
                            ->orWhereHas('researchClassGroup.defenses', fn (Builder $defense) => $defense
                                ->whereColumn('defenses.defense_type', 'documents.document_stage')
                                ->whereIn('defenses.status', ['scheduled', 'completed'])
                                ->whereHas('activePanelAssignments', fn (Builder $assignment) => $assignment
                                    ->where('user_id', $user->getKey())));
                    });
            })->orWhere(function (Builder $legacy) use ($user): void {
                $legacy->whereNull('documents.research_class_group_id')
                    ->where(function (Builder $legacyAccess) use ($user): void {
                        $legacyAccess->where('documents.user_id', $user->getKey());

                        if ($user->can('documents.review')) {
                            $legacyAccess->orWhere(function (Builder $review) use ($user): void {
                                $this->reviewerAccess->scopeFor($review, $user);
                            });
                        }
                    });
            });
        });
    }

    private function isActivePanelistForDocumentStage(User $user, Document $document): bool
    {
        if ($document->research_class_group_id === null || $document->document_stage === null) {
            return false;
        }

        return Defense::query()
            ->where('research_class_group_id', $document->research_class_group_id)
            ->where('defense_type', $document->document_stage->value)
            ->whereIn('status', ['scheduled', 'completed'])
            ->whereHas('activePanelAssignments', fn (Builder $assignment) => $assignment
                ->where('user_id', $user->getKey()))
            ->exists();
    }
}
