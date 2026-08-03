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

        if ($reviewer->can('research.view-all')) {
            return true;
        }

        return $this->hasClassAccess($reviewer, $document)
            || $this->hasAssignmentAccess($reviewer, $document);
    }

    /**
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
                    ->from('research_class_group_members as review_group_members')
                    ->join(
                        'research_class_groups as review_groups',
                        'review_groups.id',
                        '=',
                        'review_group_members.research_class_group_id',
                    )
                    ->whereColumn('review_group_members.student_id', 'documents.user_id')
                    ->where('review_groups.adviser_id', $reviewer->getKey());
            });

            if ($this->assignmentTablesExist()) {
                $accessQuery->orWhereExists(function ($assignmentQuery) use ($reviewer): void {
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
            }
        });
    }

    private function hasClassAccess(User $reviewer, Document $document): bool
    {
        return DB::table('research_class_group_members as members')
            ->join('research_class_groups as groups', 'groups.id', '=', 'members.research_class_group_id')
            ->where('members.student_id', $document->user_id)
            ->where('groups.adviser_id', $reviewer->getKey())
            ->exists();
    }

    private function hasAssignmentAccess(User $reviewer, Document $document): bool
    {
        if (! $this->assignmentTablesExist()) {
            return false;
        }

        return DB::table('student_profiles as students')
            ->join('research_group_members as members', 'members.student_profile_id', '=', 'students.id')
            ->join('research_projects as projects', 'projects.research_group_id', '=', 'members.research_group_id')
            ->join('adviser_assignments as assignments', 'assignments.research_project_id', '=', 'projects.id')
            ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
            ->where('students.user_id', $document->user_id)
            ->where('faculty.user_id', $reviewer->getKey())
            ->where('assignments.status', 'active')
            ->whereNull('assignments.ended_at')
            ->whereNull('members.left_at')
            ->whereNull('projects.archived_at')
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
            fn (): bool => DB::table('information_schema.tables')
                ->where('table_schema', 'public')
                ->whereIn('table_name', $tables)
                ->distinct()
                ->count('table_name') === count($tables),
        );
    }
}
