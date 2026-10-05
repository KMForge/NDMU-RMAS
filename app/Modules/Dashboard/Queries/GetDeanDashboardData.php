<?php

namespace App\Modules\Dashboard\Queries;

use App\Enums\DocumentStatus;
use App\Models\DefenseSchedule;
use App\Models\Document;
use App\Models\ResearchClassGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class GetDeanDashboardData
{
    /** @return array<string, mixed> */
    public function for(User $dean, array $input = []): array
    {
        $filters = $this->filters($input);
        $activeTab = in_array(($input['tab'] ?? 'dashboard'), ['dashboard', 'pending', 'manuscript', 'schedule', 'repository', 'notifications', 'settings'], true)
            ? (string) ($input['tab'] ?? 'dashboard')
            : 'dashboard';
        $college = $dean->facultyProfile()
            ->with('department.college')
            ->first()?->department?->college;

        if ($college === null) {
            return [...$this->emptyData(), 'deanFilters' => $filters];
        }

        $groupScope = static fn (Builder $query): Builder => $query
            ->whereHas('researchClass.facilitator.facultyProfile.department', fn (Builder $department) => $department
                ->where('college_id', $college->getKey()));

        $groupsQuery = ResearchClassGroup::query()->tap($groupScope);
        $groups = $activeTab === 'dashboard'
            ? (clone $groupsQuery)
                ->with(['researchClass:id,name,facilitator_id', 'researchGroup.currentProject', 'adviser:id,name'])
                ->withCount('members')
                ->latest('id')
                ->limit(6)
                ->get()
            : collect();

        $groupCounts = (clone $groupsQuery)
            ->selectRaw("COUNT(*) AS total, COUNT(*) FILTER (WHERE status = 'active') AS active")
            ->first();
        $archivedResearchCount = (clone $groupsQuery)
            ->whereHas('researchGroup.currentProject', fn (Builder $project) => $project->where('status', 'archived'))
            ->count();

        $documentScope = Document::query()
            ->whereNotNull('research_class_group_id')
            ->where('is_current', true)
            ->whereHas('researchClassGroup', $groupScope);

        $documents = in_array($activeTab, ['manuscript', 'repository'], true)
            ? (clone $documentScope)
                ->when($filters['document_search'] !== '', fn (Builder $query) => $query
                    ->whereRaw('LOWER(original_filename) LIKE ?', ['%'.Str::lower($filters['document_search']).'%']))
                ->when($filters['document_status'] !== 'all', fn (Builder $query) => $query
                    ->where('status', $filters['document_status']))
                ->with([
                    'user:id,name',
                    'researchClassGroup:id,research_class_id,research_group_id,name',
                    'researchClassGroup.researchClass:id,name,facilitator_id',
                    'researchClassGroup.researchGroup.currentProject',
                ])
                ->latest('submitted_at')
                ->latest('id')
                ->get()
            : collect();

        $scheduleScope = DefenseSchedule::query()
            ->whereHas('defense.group', $groupScope);

        $schedules = $activeTab === 'schedule'
            ? (clone $scheduleScope)
                ->when($filters['schedule_status'] !== 'all', fn (Builder $query) => $query
                    ->where('status', $filters['schedule_status']))
                ->with([
                    'defense.group.researchClass:id,name,facilitator_id',
                    'defense.group.researchGroup.currentProject',
                    'defense.activePanelAssignments.user:id,name',
                    'room:id,code,name,location_notes',
                ])
                ->latest('starts_at')
                ->latest('id')
                ->get()
            : collect();

        $documentStatusCounts = (clone $documentScope)
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $pendingDocumentCount = collect([
            DocumentStatus::Pending->value,
            DocumentStatus::Submitted->value,
            DocumentStatus::UnderReview->value,
            DocumentStatus::RevisionRequested->value,
        ])->sum(fn (string $status): int => (int) $documentStatusCounts->get($status, 0));
        $upcomingDefenseCount = (clone $scheduleScope)
            ->where('starts_at', '>', now())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->count();

        return [
            'deanCollege' => $college,
            'deanGroups' => $groups,
            'deanDocuments' => $documents,
            'deanDefenseSchedules' => $schedules,
            'deanFilters' => $filters,
            'deanStats' => [
                'groups' => (int) ($groupCounts?->total ?? 0),
                'active_groups' => (int) ($groupCounts?->active ?? 0),
                'documents' => (int) $documentStatusCounts->sum(),
                'pending_documents' => $pendingDocumentCount,
                'upcoming_defenses' => $upcomingDefenseCount,
                'archived_research' => $archivedResearchCount,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function emptyData(): array
    {
        return [
            'deanCollege' => null,
            'deanGroups' => collect(),
            'deanDocuments' => collect(),
            'deanDefenseSchedules' => collect(),
            'deanStats' => [
                'groups' => 0,
                'active_groups' => 0,
                'documents' => 0,
                'pending_documents' => 0,
                'upcoming_defenses' => 0,
                'archived_research' => 0,
            ],
        ];
    }

    /** @param array<string, mixed> $input
     * @return array{document_search:string,document_status:string,schedule_status:string}
     */
    private function filters(array $input): array
    {
        $documentStatuses = ['all', ...array_column(DocumentStatus::cases(), 'value')];
        $scheduleStatuses = ['all', 'pending', 'current', 'scheduled', 'completed', 'cancelled'];

        return [
            'document_search' => Str::limit(trim(strip_tags((string) ($input['document_search'] ?? ''))), 100, ''),
            'document_status' => in_array($input['document_status'] ?? 'all', $documentStatuses, true)
                ? (string) ($input['document_status'] ?? 'all') : 'all',
            'schedule_status' => in_array($input['schedule_status'] ?? 'all', $scheduleStatuses, true)
                ? (string) ($input['schedule_status'] ?? 'all') : 'all',
        ];
    }
}
