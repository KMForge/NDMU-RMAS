<?php

namespace App\Modules\Research\Queries;

use App\Enums\DocumentStatus;
use App\Models\ConsultationRequest;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\User;
use App\Modules\Documents\Support\DocumentReviewerAccess;
use App\Support\CachesDatabaseSchema;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GetAdviserDashboardOverview
{
    use CachesDatabaseSchema;

    public function __construct(
        private readonly DocumentReviewerAccess $reviewerAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function for(User $adviser, bool $includeOverview): array
    {
        $notifications = $this->notificationsFor($adviser);

        if (! $includeOverview) {
            return [
                ...$this->emptyOverview(),
                ...$this->notificationData($notifications),
            ];
        }

        $documentScope = $this->reviewerAccess->scopeFor(Document::query(), $adviser);
        $pendingDocumentScope = (clone $documentScope)
            ->whereIn('status', [
                DocumentStatus::Pending->value,
                DocumentStatus::Submitted->value,
                DocumentStatus::UnderReview->value,
            ]);
        $todayConsultations = $this->todayConsultationsFor($adviser);
        $advisees = $this->adviseesFor($adviser);

        return [
            'adviserOverviewStats' => [
                'active_advisees' => $this->activeAdviseeCount($adviser),
                'nearing_defense' => $this->nearingDefenseCount($adviser),
                'urgent_reviews' => (clone $pendingDocumentScope)->count(),
                'overdue_revisions' => $this->overdueRevisionCount($adviser),
                'today_consultations' => $todayConsultations->count(),
                'next_consultation_at' => $todayConsultations->first()?->preferred_at,
                'completed_research' => $this->completedResearchCount($adviser),
            ],
            'adviserOverviewAdvisees' => $advisees,
            'adviserPendingDocuments' => (clone $pendingDocumentScope)
                ->with('user:id,name')
                ->latest('submitted_at')
                ->limit(3)
                ->get(),
            'adviserTodayConsultations' => $todayConsultations,
            'adviserRecentActivity' => $this->recentActivityFor($adviser),
            ...$this->notificationData($notifications),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function adviseesFor(User $adviser): Collection
    {
        if (! $this->tablesExist([
            'research_class_groups',
            'research_class_group_members',
            'users',
        ])) {
            return collect();
        }

        return DB::table('research_class_group_members as members')
            ->join('research_class_groups as groups', 'groups.id', '=', 'members.research_class_group_id')
            ->join('users as students', 'students.id', '=', 'members.student_id')
            ->where('groups.adviser_id', $adviser->getKey())
            ->select(['students.id', 'students.name'])
            ->distinct()
            ->orderBy('students.name')
            ->limit(5)
            ->get()
            ->map(function (object $student): array {
                $project = $this->projectForStudent((int) $student->id);
                $progress = $project === null
                    ? 0
                    : $this->projectProgress((int) $project->id);

                return [
                    'id' => (int) $student->id,
                    'name' => $student->name,
                    'project' => $project?->title,
                    'status' => $project?->status,
                    'progress' => $progress,
                    'avatar' => Str::upper(Str::substr($student->name, 0, 1)),
                ];
            });
    }

    private function projectForStudent(int $studentId): ?object
    {
        if (! $this->tablesExist([
            'student_profiles',
            'research_group_members',
            'research_projects',
        ])) {
            return null;
        }

        return DB::table('student_profiles as profiles')
            ->join(
                'research_group_members as members',
                'members.student_profile_id',
                '=',
                'profiles.id',
            )
            ->join(
                'research_projects as projects',
                'projects.research_group_id',
                '=',
                'members.research_group_id',
            )
            ->where('profiles.user_id', $studentId)
            ->whereNull('members.left_at')
            ->whereNull('projects.archived_at')
            ->select(['projects.id', 'projects.title', 'projects.status'])
            ->latest('projects.updated_at')
            ->first();
    }

    private function projectProgress(int $projectId): int
    {
        if (! $this->tableExists('research_progress_updates')) {
            return 0;
        }

        return (int) min(100, max(
            0,
            (float) (DB::table('research_progress_updates')
                ->where('research_project_id', $projectId)
                ->latest('created_at')
                ->value('progress_percentage') ?? 0),
        ));
    }

    private function activeAdviseeCount(User $adviser): int
    {
        if (! $this->tablesExist(['research_class_groups', 'research_class_group_members'])) {
            return 0;
        }

        return DB::table('research_class_group_members as members')
            ->join('research_class_groups as groups', 'groups.id', '=', 'members.research_class_group_id')
            ->where('groups.adviser_id', $adviser->getKey())
            ->distinct()
            ->count('members.student_id');
    }

    private function completedResearchCount(User $adviser): int
    {
        if (! $this->tablesExist([
            'research_projects',
            'adviser_assignments',
            'faculty_profiles',
        ])) {
            return 0;
        }

        return DB::table('research_projects as projects')
            ->join(
                'adviser_assignments as assignments',
                'assignments.research_project_id',
                '=',
                'projects.id',
            )
            ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
            ->where('faculty.user_id', $adviser->getKey())
            ->where('projects.status', 'completed')
            ->distinct()
            ->count('projects.id');
    }

    private function nearingDefenseCount(User $adviser): int
    {
        if (! $this->tablesExist([
            'defense_requests',
            'defense_schedules',
            'adviser_assignments',
            'faculty_profiles',
        ])) {
            return 0;
        }

        return DB::table('defense_schedules as schedules')
            ->join(
                'defense_requests as requests',
                'requests.id',
                '=',
                'schedules.defense_request_id',
            )
            ->join(
                'adviser_assignments as assignments',
                'assignments.research_project_id',
                '=',
                'requests.research_project_id',
            )
            ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
            ->where('faculty.user_id', $adviser->getKey())
            ->where('schedules.starts_at', '>=', now())
            ->distinct()
            ->count('requests.research_project_id');
    }

    private function overdueRevisionCount(User $adviser): int
    {
        if (! $this->tableExists('revision_requests')) {
            return 0;
        }

        return DB::table('revision_requests')
            ->where('requested_by', $adviser->getKey())
            ->whereIn('status', ['open', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();
    }

    /**
     * @return Collection<int, object>
     */
    private function todayConsultationsFor(User $adviser): Collection
    {
        if (! $this->tablesExist([
            'consultation_requests',
            'adviser_assignments',
            'faculty_profiles',
            'users',
        ])) {
            return collect();
        }

        $timezone = (string) config('ndmu-rmas.timezone', config('app.timezone'));
        $start = now($timezone)->startOfDay()->utc();
        $end = now($timezone)->endOfDay()->utc();

        return ConsultationRequest::query()
            ->join(
                'adviser_assignments as assignments',
                'assignments.id',
                '=',
                'consultation_requests.adviser_assignment_id',
            )
            ->join('faculty_profiles as faculty', 'faculty.id', '=', 'assignments.adviser_id')
            ->join('users as students', 'students.id', '=', 'consultation_requests.requested_by')
            ->where('faculty.user_id', $adviser->getKey())
            ->where('consultation_requests.status', 'approved')
            ->whereBetween('consultation_requests.preferred_at', [$start, $end])
            ->select([
                'consultation_requests.id',
                'consultation_requests.preferred_at',
                'consultation_requests.agenda',
                'consultation_requests.consultation_mode',
                'students.name as student_name',
            ])
            ->orderBy('consultation_requests.preferred_at')
            ->get();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function recentActivityFor(User $adviser): Collection
    {
        if (! $this->tablesExist(['document_reviews', 'documents', 'users'])) {
            return collect();
        }

        return DocumentReview::query()
            ->with('document.user:id,name')
            ->where('reviewer_id', $adviser->getKey())
            ->latest('reviewed_at')
            ->limit(5)
            ->get()
            ->map(fn (DocumentReview $review): array => [
                'title' => Str::headline($review->decision).' document',
                'student_name' => $review->document?->user?->name,
                'occurred_at' => $review->reviewed_at,
                'icon' => $review->decision === DocumentStatus::Accepted->value
                    ? 'ph-check'
                    : 'ph-file-text',
            ]);
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function notificationsFor(User $adviser): Collection
    {
        if (! $this->tableExists('notifications')) {
            return collect();
        }

        return $adviser->notifications()->latest()->limit(50)->get();
    }

    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return array<string, mixed>
     */
    private function notificationData(Collection $notifications): array
    {
        $items = $notifications->map(function (DatabaseNotification $notification): array {
            $data = $notification->data;
            $category = $this->notificationCategory((string) ($data['type'] ?? 'system'));

            return [
                'id' => $notification->getKey(),
                'type' => $data['type'] ?? 'system',
                'title' => $data['title'] ?? 'Notification',
                'isNew' => $notification->read_at === null,
                'badge' => Str::headline($category),
                'badgeClass' => $this->notificationBadgeClass($category),
                'description' => $data['message'] ?? '',
                'time' => $notification->created_at?->diffForHumans(),
                'icon' => $this->notificationIcon($category),
                'iconBg' => $this->notificationIconClass($category),
                'category' => $category,
                'unread' => $notification->read_at === null,
                'hasActions' => false,
            ];
        })->values();

        return [
            'adviserNotifications' => $items,
            'adviserNotificationStats' => [
                'total' => $items->count(),
                'unread' => $items->where('unread', true)->count(),
                'approvals' => $items->where('category', 'approvals')->count(),
                'defense' => $items->where('category', 'defense')->count(),
                'documents' => $items->where('category', 'documents')->count(),
                'system' => $items->where('category', 'system')->count(),
            ],
        ];
    }

    private function notificationCategory(string $type): string
    {
        return match ($type) {
            'document' => 'documents',
            'defense' => 'defense',
            'research', 'evaluation' => 'approvals',
            default => 'system',
        };
    }

    private function notificationBadgeClass(string $category): string
    {
        return match ($category) {
            'documents' => 'bg-blue-50 border border-blue-100 text-blue-700',
            'defense' => 'bg-purple-50 border border-purple-100 text-purple-700',
            'approvals' => 'bg-emerald-50 border border-emerald-100 text-emerald-700',
            default => 'bg-gray-50 border border-gray-100 text-gray-700',
        };
    }

    private function notificationIcon(string $category): string
    {
        return match ($category) {
            'documents' => 'ph ph-file-text',
            'defense' => 'ph ph-calendar',
            'approvals' => 'ph ph-check-square',
            default => 'ph ph-gear',
        };
    }

    private function notificationIconClass(string $category): string
    {
        return match ($category) {
            'documents' => 'bg-blue-50 text-blue-600',
            'defense' => 'bg-purple-50 text-purple-600',
            'approvals' => 'bg-emerald-50 text-emerald-600',
            default => 'bg-gray-50 text-gray-600',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyOverview(): array
    {
        return [
            'adviserOverviewStats' => [
                'active_advisees' => 0,
                'nearing_defense' => 0,
                'urgent_reviews' => 0,
                'overdue_revisions' => 0,
                'today_consultations' => 0,
                'next_consultation_at' => null,
                'completed_research' => 0,
            ],
            'adviserOverviewAdvisees' => collect(),
            'adviserPendingDocuments' => collect(),
            'adviserTodayConsultations' => collect(),
            'adviserRecentActivity' => collect(),
        ];
    }
}
