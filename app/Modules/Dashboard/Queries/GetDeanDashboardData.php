<?php

namespace App\Modules\Dashboard\Queries;

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
        $college = $dean->facultyProfile()
            ->with('department.college')
            ->first()?->department?->college;

        if ($college === null) {
            return [...$this->emptyData(), 'deanFilters' => $filters];
        }

        $groupScope = static fn (Builder $query): Builder => $query
            ->whereHas('researchClass.facilitator.facultyProfile.department', fn (Builder $department) => $department
                ->where('college_id', $college->getKey()));

        $groups = ResearchClassGroup::query()
            ->tap($groupScope)
            ->with(['researchClass:id,name,facilitator_id', 'researchGroup.currentProject', 'adviser:id,name'])
            ->withCount('members')
            ->latest('id')
            ->get();

        $documents = Document::query()
            ->whereNotNull('research_class_group_id')
            ->where('is_current', true)
            ->whereHas('researchClassGroup', $groupScope)
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
            ->get();

        $schedules = DefenseSchedule::query()
            ->whereHas('defense.group', $groupScope)
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
            ->get();

        return [
            'deanCollege' => $college,
            'deanGroups' => $groups,
            'deanDocuments' => $documents,
            'deanDefenseSchedules' => $schedules,
            'deanFilters' => $filters,
            'deanStats' => [
                'groups' => $groups->count(),
                'active_groups' => $groups->where('status', 'active')->count(),
                'documents' => $documents->count(),
                'pending_documents' => $documents->filter(fn (Document $document): bool => in_array(
                    $document->status?->value ?? (string) $document->status,
                    ['pending', 'submitted', 'under_review'],
                    true,
                ))->count(),
                'upcoming_defenses' => $schedules->filter(fn (DefenseSchedule $schedule): bool => $schedule->starts_at?->isFuture()
                    && ! in_array($schedule->status, ['cancelled', 'completed'], true)
                )->count(),
                'archived_research' => $groups->filter(fn (ResearchClassGroup $group): bool => $group->researchGroup?->currentProject?->status === 'archived'
                )->count(),
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
        $documentStatuses = ['all', 'pending', 'submitted', 'under_review', 'accepted', 'rejected'];
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
