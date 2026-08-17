<?php

namespace App\Modules\Documents\Support;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DocumentReviewerAccess
{
    public function canReview(User $reviewer, Document $document): bool
    {
        if (! $reviewer->can('documents.review')) {
            return false;
        }

        if ($document->research_class_group_id !== null) {
            return $this->hasCurrentGroupAccess($reviewer, $document);
        }

        return false;
    }

    /**
     * Scopes documents specifically for the Adviser Document Review Queue.
     * Requires documents.review permission and active assigned adviser relationship.
     * Broad permissions (research.view-all, admin, facilitator) do NOT broaden this queue.
     *
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopeForReviewQueue(Builder $query, User $reviewer): Builder
    {
        if (! $reviewer->can('documents.review')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereExists(function ($classQuery) use ($reviewer): void {
            $classQuery
                ->selectRaw('1')
                ->from('research_class_groups as review_groups')
                ->whereColumn('review_groups.id', 'documents.research_class_group_id')
                ->where('review_groups.adviser_id', $reviewer->getKey())
                ->where('review_groups.status', 'active')
                ->whereNull('review_groups.disbanded_at');
        });
    }

    /**
     * General reviewer access query scope (used by Phase 14 repository access for legacy compatibility).
     *
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function scopeFor(Builder $query, User $reviewer): Builder
    {
        if ($reviewer->can('research.view-all')) {
            return $query;
        }

        return $query->where(function (Builder $accessQuery) use ($reviewer): void {
            $accessQuery->whereExists(function ($classQuery) use ($reviewer): void {
                $classQuery
                    ->selectRaw('1')
                    ->from('research_class_groups as review_groups')
                    ->whereColumn('review_groups.id', 'documents.research_class_group_id')
                    ->where('review_groups.adviser_id', $reviewer->getKey())
                    ->where('review_groups.status', 'active')
                    ->whereNull('review_groups.disbanded_at');
            });

            if ($this->assignmentTablesExist()) {
                $accessQuery->orWhere(function (Builder $legacyQuery) use ($reviewer): void {
                    $legacyQuery
                        ->whereNull('documents.research_class_group_id')
                        ->whereExists(function ($assignmentQuery) use ($reviewer): void {
                            $assignmentQuery
                                ->selectRaw('1')
                                ->from('student_profiles as review_students')
                                ->join(
                                    'research_group_members as review_members',
                                    'review_members.student_profile_id',
                                    '=',
                                    'review_students.id',
                                )
                                ->join(
                                    'research_projects as review_projects',
                                    'review_projects.research_group_id',
                                    '=',
                                    'review_members.research_group_id',
                                )
                                ->join(
                                    'adviser_assignments as review_assignments',
                                    'review_assignments.research_project_id',
                                    '=',
                                    'review_projects.id',
                                )
                                ->join(
                                    'faculty_profiles as review_faculty',
                                    'review_faculty.id',
                                    '=',
                                    'review_assignments.adviser_id',
                                )
                                ->whereColumn('review_students.user_id', 'documents.user_id')
                                ->where('review_faculty.user_id', $reviewer->getKey())
                                ->where('review_assignments.status', 'active')
                                ->whereNull('review_assignments.ended_at')
                                ->whereNull('review_members.left_at')
                                ->whereNull('review_projects.archived_at');
                        });
                });
            }
        });
    }

    private function hasCurrentGroupAccess(User $reviewer, Document $document): bool
    {
        return DB::table('research_class_groups')
            ->where('id', $document->research_class_group_id)
            ->where('adviser_id', $reviewer->getKey())
            ->where('status', 'active')
            ->whereNull('disbanded_at')
            ->exists();
    }

    private function assignmentTablesExist(): bool
    {
        $tables = [
            'student_profiles',
            'research_group_members',
            'research_projects',
            'adviser_assignments',
            'faculty_profiles',
        ];

        if (app()->environment('testing')) {
            return collect($tables)->every(
                fn (string $table): bool => Schema::hasTable($table),
            );
        }

        return Cache::remember(
            'schema:document-review-assignment-tables:v1',
            now()->addHour(),
            fn (): bool => collect($tables)->every(
                fn (string $table): bool => Schema::hasTable($table),
            ),
        );
    }
}
