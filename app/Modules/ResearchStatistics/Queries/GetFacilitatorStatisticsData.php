<?php

namespace App\Modules\ResearchStatistics\Queries;

use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\ResearchProgress\Services\ResearchJourneyService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GetFacilitatorStatisticsData
{
    public function __construct(private readonly ResearchJourneyService $journey) {}

    /** @param array<string, mixed> $input */
    public function for(User $facilitator, array $input = []): array
    {
        $options = $this->filterOptions($facilitator);
        $filters = $this->filters($input, $options);

        $groupRows = $this->groupRows($facilitator, $filters);
        $groupIds = $groupRows->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $groups = ResearchClassGroup::query()
            ->whereIn('id', $groupIds ?: [-1])
            ->with(['researchClass:id,name,facilitator_id', 'researchGroup.currentProject'])
            ->get()
            ->keyBy('id');

        $journeys = $groupRows->mapWithKeys(function (object $row) use ($groups, $facilitator): array {
            $group = $groups->get((int) $row->id);

            return $group === null ? [] : [(int) $row->id => $this->journey->getJourneyForGroup($group, $facilitator)];
        });

        $completedIds = $journeys
            ->filter(fn (array $journey): bool => (int) $journey['percentage'] >= 100)
            ->keys();
        $blockedIds = $journeys
            ->filter(fn (array $journey): bool => ($journey['stage_status'] ?? null) === 'blocked' || ($journey['blockers'] ?? []) !== [])
            ->keys();

        $overdueRevisionIds = $this->overdueRevisionGroupIds($groupIds);
        $overdueMilestoneIds = $this->overdueMilestoneGroupIds($groupIds);
        $overdueIds = $overdueRevisionIds->merge($overdueMilestoneIds)->unique()->values();
        $completed = $completedIds->count();
        $total = $groupRows->count();
        $inProgress = max(0, $total - $completed);

        return [
            'statistics' => [
                'filters' => $filters,
                'options' => $options,
                'scope_label' => 'Your assigned research classes',
                'kpis' => [
                    'total' => $total,
                    'completed' => $completed,
                    'in_progress' => $inProgress,
                    'blocked' => $blockedIds->count(),
                    'overdue' => $overdueIds->count(),
                    'completion_rate' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
                    'average_duration_months' => $this->averageCompletionDuration($groupRows, $completedIds),
                ],
                'programs' => $this->programDistribution($groupRows),
                'lifecycle' => $this->lifecycleDistribution($journeys),
                'monthly_submissions' => $this->monthlySubmissions($groupIds, $filters, $options),
                'operations' => $this->operationalMetrics($groupIds),
                'attention' => $this->attentionMetrics($groupRows, $groupIds, $blockedIds, $overdueIds),
                'generated_at' => now(),
            ],
        ];
    }

    /** @return array<string, Collection<int, object>> */
    private function filterOptions(User $facilitator): array
    {
        $base = DB::table('research_class_groups as rcg')
            ->join('research_classes as rc', 'rc.id', '=', 'rcg.research_class_id')
            ->leftJoin('research_groups as rg', 'rg.id', '=', 'rcg.research_group_id')
            ->leftJoin('programs as p', 'p.id', '=', 'rg.program_id')
            ->leftJoin('academic_terms as at', 'at.id', '=', 'rg.academic_term_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'at.academic_year_id')
            ->leftJoin('users as adviser', 'adviser.id', '=', 'rcg.adviser_id')
            ->where('rc.facilitator_id', $facilitator->id);

        return [
            'years' => (clone $base)->whereNotNull('ay.id')->select(['ay.id', 'ay.name', 'ay.starts_at', 'ay.ends_at', 'ay.is_current'])->distinct()->orderByDesc('ay.starts_at')->get(),
            'terms' => (clone $base)->whereNotNull('at.id')->select(['at.id', 'at.name', 'at.academic_year_id', 'at.starts_at'])->distinct()->orderBy('at.starts_at')->get(),
            'programs' => (clone $base)->whereNotNull('p.id')->select(['p.id', 'p.name', 'p.code'])->distinct()->orderBy('p.name')->get(),
            'classes' => (clone $base)->select(['rc.id', 'rc.name'])->distinct()->orderBy('rc.name')->get(),
            'advisers' => (clone $base)->whereNotNull('adviser.id')->select(['adviser.id', 'adviser.name'])->distinct()->orderBy('adviser.name')->get(),
        ];
    }

    /** @param array<string, Collection<int, object>> $options */
    private function filters(array $input, array $options): array
    {
        $allowed = function (string $key, string $option, string $column = 'id') use ($input, $options): ?int {
            $value = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT);

            return $value && $options[$option]->contains(fn (object $item): bool => (int) $item->{$column} === $value)
                ? $value
                : null;
        };

        $yearId = $allowed('statistics_academic_year_id', 'years')
            ?? (int) ($options['years']->firstWhere('is_current', true)?->id ?? $options['years']->first()?->id ?? 0)
            ?: null;
        $termId = $allowed('statistics_academic_term_id', 'terms');
        if ($termId !== null && (int) $options['terms']->firstWhere('id', $termId)?->academic_year_id !== $yearId) {
            $termId = null;
        }

        return [
            'academic_year_id' => $yearId,
            'academic_term_id' => $termId,
            'program_id' => $allowed('statistics_program_id', 'programs'),
            'research_class_id' => $allowed('statistics_research_class_id', 'classes'),
            'adviser_id' => $allowed('statistics_adviser_id', 'advisers'),
        ];
    }

    /** @return Collection<int, object> */
    private function groupRows(User $facilitator, array $filters): Collection
    {
        return DB::table('research_class_groups as rcg')
            ->join('research_classes as rc', 'rc.id', '=', 'rcg.research_class_id')
            ->leftJoin('research_groups as rg', 'rg.id', '=', 'rcg.research_group_id')
            ->leftJoin('programs as p', 'p.id', '=', 'rg.program_id')
            ->leftJoin('academic_terms as at', 'at.id', '=', 'rg.academic_term_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'at.academic_year_id')
            ->where('rc.facilitator_id', $facilitator->id)
            ->when($filters['academic_year_id'], fn ($query, $id) => $query->where('ay.id', $id))
            ->when($filters['academic_term_id'], fn ($query, $id) => $query->where('at.id', $id))
            ->when($filters['program_id'], fn ($query, $id) => $query->where('p.id', $id))
            ->when($filters['research_class_id'], fn ($query, $id) => $query->where('rc.id', $id))
            ->when($filters['adviser_id'], fn ($query, $id) => $query->where('rcg.adviser_id', $id))
            ->select([
                'rcg.id', 'rcg.name', 'rcg.status', 'rcg.adviser_id', 'rcg.created_at', 'rcg.updated_at',
                'rc.name as class_name', 'p.id as program_id', 'p.code as program_code', 'p.name as program_name',
            ])
            ->orderBy('rcg.name')
            ->get();
    }

    private function overdueRevisionGroupIds(array $groupIds): Collection
    {
        return DB::table('revision_requests')
            ->whereIn('research_class_group_id', $groupIds ?: [-1])
            ->whereNotIn('status', ['resolved', 'cancelled'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->pluck('research_class_group_id');
    }

    private function overdueMilestoneGroupIds(array $groupIds): Collection
    {
        return DB::table('research_group_milestones')
            ->whereIn('research_class_group_id', $groupIds ?: [-1])
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->pluck('research_class_group_id');
    }

    private function averageCompletionDuration(Collection $groupRows, Collection $completedIds): ?float
    {
        if ($completedIds->isEmpty()) {
            return null;
        }

        $completedAt = DB::table('research_group_milestones')
            ->whereIn('research_class_group_id', $completedIds)
            ->whereNotNull('completed_at')
            ->selectRaw('research_class_group_id, MAX(completed_at) as completed_at')
            ->groupBy('research_class_group_id')
            ->pluck('completed_at', 'research_class_group_id');

        $durations = $groupRows
            ->whereIn('id', $completedIds)
            ->map(function (object $row) use ($completedAt): ?float {
                $finished = $completedAt->get($row->id);

                return $finished === null
                    ? null
                    : CarbonImmutable::parse($row->created_at)->diffInDays(CarbonImmutable::parse($finished)) / 30.4375;
            })
            ->filter(fn ($months): bool => $months !== null);

        return $durations->isEmpty() ? null : round((float) $durations->average(), 1);
    }

    /** @return list<array<string, mixed>> */
    private function programDistribution(Collection $groupRows): array
    {
        $counts = $groupRows->groupBy(fn (object $row): string => $row->program_name ?: 'Program not assigned')->map->count();
        $maximum = max(1, (int) $counts->max());

        return $counts->sortDesc()->map(fn (int $count, string $name): array => [
            'name' => $name,
            'count' => $count,
            'percentage' => (int) round(($count / $maximum) * 100),
        ])->values()->all();
    }

    /** @return list<array<string, mixed>> */
    private function lifecycleDistribution(Collection $journeys): array
    {
        return $journeys
            ->groupBy(fn (array $journey): string => (int) $journey['percentage'] >= 100
                ? 'Completed'
                : 'Stage '.(int) $journey['current_stage'].' — '.$journey['current_stage_name'])
            ->map(fn (Collection $items, string $label): array => ['label' => $label, 'count' => $items->count()])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /** @param array<string, Collection<int, object>> $options */
    private function monthlySubmissions(array $groupIds, array $filters, array $options): array
    {
        $year = $options['years']->firstWhere('id', $filters['academic_year_id']);
        $start = CarbonImmutable::parse($year?->starts_at ?? now()->startOfYear())->startOfMonth();
        $end = CarbonImmutable::parse($year?->ends_at ?? now()->endOfYear())->endOfMonth();
        $documents = DB::table('documents')
            ->whereIn('research_class_group_id', $groupIds ?: [-1])
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$start, $end])
            ->get(['submitted_at']);

        $counts = $documents->countBy(fn (object $document): string => CarbonImmutable::parse($document->submitted_at)->format('Y-m'));
        $months = collect();
        for ($month = $start; $month->lte($end); $month = $month->addMonth()) {
            $months->push([
                'key' => $month->format('Y-m'),
                'label' => $month->format('M'),
                'count' => (int) $counts->get($month->format('Y-m'), 0),
            ]);
        }
        $maximum = max(1, (int) $months->max('count'));

        return $months->map(fn (array $item): array => [
            ...$item,
            'percentage' => (int) round(($item['count'] / $maximum) * 100),
        ])->all();
    }

    private function operationalMetrics(array $groupIds): array
    {
        $documents = DB::table('documents')->whereIn('research_class_group_id', $groupIds ?: [-1]);
        $revisions = DB::table('revision_requests')->whereIn('research_class_group_id', $groupIds ?: [-1]);
        $defenses = DB::table('defenses')->whereIn('research_class_group_id', $groupIds ?: [-1]);
        $forms = DB::table('official_form_instances')->whereIn('research_class_group_id', $groupIds ?: [-1]);

        return [
            'documents_awaiting_review' => (clone $documents)->whereIn('status', ['pending', 'submitted', 'under_review'])->count(),
            'revisions_open' => (clone $revisions)->whereNotIn('status', ['resolved', 'cancelled'])->count(),
            'revisions_overdue' => (clone $revisions)->whereNotIn('status', ['resolved', 'cancelled'])->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'defenses_scheduled' => (clone $defenses)->whereIn('status', ['scheduled', 'in_progress', 'rescheduled'])->count(),
            'defenses_completed' => (clone $defenses)->where('status', 'completed')->count(),
            'defenses_unscheduled' => (clone $defenses)->where('status', 'draft')->count(),
            'forms_awaiting_action' => (clone $forms)->whereIn('status', ['submitted', 'endorsed', 'in_progress'])->count(),
        ];
    }

    private function attentionMetrics(Collection $groupRows, array $groupIds, Collection $blockedIds, Collection $overdueIds): array
    {
        $latestDocumentActivity = DB::table('documents')->whereIn('research_class_group_id', $groupIds ?: [-1])
            ->selectRaw('research_class_group_id, MAX(updated_at) as activity_at')->groupBy('research_class_group_id')->pluck('activity_at', 'research_class_group_id');
        $latestFormActivity = DB::table('official_form_instances')->whereIn('research_class_group_id', $groupIds ?: [-1])
            ->selectRaw('research_class_group_id, MAX(updated_at) as activity_at')->groupBy('research_class_group_id')->pluck('activity_at', 'research_class_group_id');

        $inactive = $groupRows->filter(function (object $row) use ($latestDocumentActivity, $latestFormActivity): bool {
            $latest = collect([$row->updated_at, $latestDocumentActivity->get($row->id), $latestFormActivity->get($row->id)])
                ->filter()->map(fn ($date) => CarbonImmutable::parse($date))->sortDesc()->first();

            return $latest !== null && $latest->lt(now()->subDays(30));
        });

        return [
            ['label' => 'Blocked groups', 'count' => $blockedIds->count(), 'description' => 'Groups with unresolved lifecycle blockers', 'tone' => 'rose', 'tab' => 'monitoring'],
            ['label' => 'Overdue requirements', 'count' => $overdueIds->count(), 'description' => 'Groups with overdue milestones or revisions', 'tone' => 'amber', 'tab' => 'monitoring'],
            ['label' => 'No adviser assigned', 'count' => $groupRows->whereNull('adviser_id')->count(), 'description' => 'Active groups requiring an adviser', 'tone' => 'violet', 'tab' => 'classes'],
            ['label' => 'No activity for 30 days', 'count' => $inactive->count(), 'description' => 'Groups that may need facilitator follow-up', 'tone' => 'slate', 'tab' => 'monitoring'],
        ];
    }
}
