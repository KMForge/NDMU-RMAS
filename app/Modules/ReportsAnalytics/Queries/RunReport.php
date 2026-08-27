<?php

namespace App\Modules\ReportsAnalytics\Queries;

use App\Modules\ReportsAnalytics\ReportCatalog;
use App\Modules\ReportsAnalytics\ValueObjects\ReportFilters;
use App\Modules\ReportsAnalytics\ValueObjects\ReportScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RunReport
{
    /** @return array{columns:list<string>,rows:list<array<string,mixed>>,summary:array<string,mixed>,total_count?:int} */
    public function execute(string $report, ReportScope $scope, ReportFilters $filters): array
    {
        $definition = app(ReportCatalog::class)->get($report);
        $this->validateScope($scope, $filters);

        if ($this->isDetailReport($report)) {
            $allRows = $this->getDetailRows($report, $scope, $filters);
            $summary = ['row_count' => count($allRows)];
            if ($report === 'research-summary') {
                $summary['definition'] = 'Overdue requires an incomplete milestone or unresolved revision with a past due date. Delayed is unavailable in the current schema.';
            }

            return [
                'columns' => $definition['columns'],
                'rows' => $allRows,
                'summary' => $summary,
                'total_count' => count($allRows),
            ];
        }

        $key = implode(':', ['reports', $definition['version'], $report, $scope->fingerprint(), $filters->fingerprint()]);

        return Cache::remember($key, max(1, (int) config('analytics.cache_ttl', 900)), function () use ($report, $scope, $filters, $definition): array {
            $handlers = [
                'research-summary' => fn () => $this->statusSummary($scope, $filters),
                'research-output-program' => fn () => $this->outputByProgram($scope, $filters),
                'research-throughput' => fn () => $this->throughput($scope, $filters),
                'defense-types' => fn () => $this->defenseTypes($scope, $filters),
                'defense-status' => fn () => $this->defenseStatuses($scope, $filters),
                'adviser-workload' => fn () => $this->adviserWorkload($scope, $filters),
                'document-review-status' => fn () => $this->documentReviewStatus($scope, $filters),
                'revision-summary' => fn () => $this->revisionSummary($scope, $filters),
                'evaluation-release-status' => fn () => $this->evaluationRelease($scope, $filters),
            ];

            abort_unless(isset($handlers[$report]), 404);

            return ['columns' => $definition['columns']] + $handlers[$report]();
        });
    }

    public function isDetailReport(string $report): bool
    {
        return in_array($report, ['research-stage-status', 'milestone-completion'], true);
    }

    public function countDetail(string $report, ReportScope $scope, ReportFilters $filters): int
    {
        $this->validateScope($scope, $filters);

        return $this->baseGroups($scope, $filters)->count('rcg.id');
    }

    /** @return LengthAwarePaginator<array<string,mixed>> */
    public function paginateDetail(string $report, ReportScope $scope, ReportFilters $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $this->validateScope($scope, $filters);
        $definition = app(ReportCatalog::class)->get($report);
        $baseQuery = $this->baseGroups($scope, $filters);
        $total = $baseQuery->count('rcg.id');

        $groups = $baseQuery->select([
            'rcg.id', 'rcg.name', 'rcg.status as group_status', 'rcg.research_class_id',
            'rc.name as class_name', 'rcg.adviser_id', 'u.name as adviser_name',
            'p.id as program_id', 'p.name as program_name', 'at.id as term_id',
            'at.name as term_name', 'ay.id as year_id', 'ay.name as year_name', 'rcg.created_at',
        ])
            ->orderBy('rcg.name')
            ->orderBy('rcg.id')
            ->forPage($page, $perPage)
            ->get();

        $rows = $this->hydrateGroupRows($groups, $filters, $report);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $rows,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /** @return list<array<string,mixed>> */
    public function getDetailRows(string $report, ReportScope $scope, ReportFilters $filters): array
    {
        $groups = $this->baseGroups($scope, $filters)
            ->select([
                'rcg.id', 'rcg.name', 'rcg.status as group_status', 'rcg.research_class_id',
                'rc.name as class_name', 'rcg.adviser_id', 'u.name as adviser_name',
                'p.id as program_id', 'p.name as program_name', 'at.id as term_id',
                'at.name as term_name', 'ay.id as year_id', 'ay.name as year_name', 'rcg.created_at',
            ])
            ->orderBy('rcg.name')
            ->orderBy('rcg.id')
            ->get();

        return $this->hydrateGroupRows($groups, $filters, $report);
    }

    /** @return Collection<int, object> */
    public function filterOptions(ReportScope $scope): Collection
    {
        $years = DB::table('academic_years')
            ->orderBy('name')
            ->get(['id as year_id', 'name as year_name']);

        $terms = DB::table('academic_terms')
            ->orderBy('name')
            ->get(['id as term_id', 'name as term_name', 'academic_year_id as year_id']);

        $programs = DB::table('programs as p')
            ->join('departments as d', 'd.id', '=', 'p.department_id')
            ->join('colleges as c', 'c.id', '=', 'd.college_id')
            ->where('c.code', config('academic.college.code', 'CEAC'))
            ->orderBy('p.name')
            ->get(['p.id as program_id', 'p.name as program_name']);

        $classesQuery = DB::table('research_classes as rc');
        if ($scope->isFacilitator()) {
            $classesQuery->where('rc.facilitator_id', $scope->actorId);
        }
        $classes = $classesQuery->orderBy('rc.name')->get(['rc.id as research_class_id', 'rc.name as class_name']);

        $advisers = $this->baseGroups($scope, new ReportFilters)
            ->whereNotNull('rcg.adviser_id')
            ->select(['u.id as adviser_id', 'u.name as adviser_name'])
            ->distinct()
            ->orderBy('u.name')
            ->get();

        $options = collect();
        foreach ($years as $y) {
            $options->push((object) ['year_id' => $y->year_id, 'year_name' => $y->year_name, 'term_id' => null, 'term_name' => null, 'program_id' => null, 'program_name' => null, 'research_class_id' => null, 'class_name' => null, 'adviser_id' => null, 'adviser_name' => null]);
        }
        foreach ($terms as $t) {
            $options->push((object) ['year_id' => $t->year_id, 'year_name' => null, 'term_id' => $t->term_id, 'term_name' => $t->term_name, 'program_id' => null, 'program_name' => null, 'research_class_id' => null, 'class_name' => null, 'adviser_id' => null, 'adviser_name' => null]);
        }
        foreach ($programs as $p) {
            $options->push((object) ['year_id' => null, 'year_name' => null, 'term_id' => null, 'term_name' => null, 'program_id' => $p->program_id, 'program_name' => $p->program_name, 'research_class_id' => null, 'class_name' => null, 'adviser_id' => null, 'adviser_name' => null]);
        }
        foreach ($classes as $c) {
            $options->push((object) ['year_id' => null, 'year_name' => null, 'term_id' => null, 'term_name' => null, 'program_id' => null, 'program_name' => null, 'research_class_id' => $c->research_class_id, 'class_name' => $c->class_name, 'adviser_id' => null, 'adviser_name' => null]);
        }
        foreach ($advisers as $a) {
            $options->push((object) ['year_id' => null, 'year_name' => null, 'term_id' => null, 'term_name' => null, 'program_id' => null, 'program_name' => null, 'research_class_id' => null, 'class_name' => null, 'adviser_id' => $a->adviser_id, 'adviser_name' => $a->adviser_name]);
        }

        return $options;
    }

    /**
     * @param  Collection<int, object>  $groups
     * @return list<array<string, mixed>>
     */
    private function hydrateGroupRows(Collection $groups, ReportFilters $filters, string $report): array
    {
        $ids = $groups->pluck('id');
        if ($ids->isEmpty()) {
            return [];
        }

        $milestones = DB::table('research_group_milestones as gm')
            ->join('milestone_definitions as md', 'md.id', '=', 'gm.milestone_definition_id')
            ->whereIn('gm.research_class_group_id', $ids)
            ->select(['gm.research_class_group_id', 'gm.id', 'gm.status', 'gm.due_at', 'gm.completed_at', 'md.code', 'md.name', 'md.sequence', 'md.weight'])
            ->orderBy('md.sequence')->get()->groupBy('research_class_group_id');

        $revisions = DB::table('revision_requests')
            ->whereIn('research_class_group_id', $ids)
            ->select(['id', 'research_class_group_id', 'status', 'due_at', 'resolved_at', 'created_at'])
            ->get()->groupBy('research_class_group_id');

        $rows = [];
        foreach ($groups as $group) {
            $groupMilestones = collect($milestones->get($group->id, []));
            $groupRevisions = collect($revisions->get($group->id, []));
            $applicable = $groupMilestones->where('status', '!=', 'not_applicable');
            $completedWeight = (float) $applicable->where('status', 'completed')->sum(fn ($item) => (float) $item->weight);
            $applicableWeight = (float) $applicable->sum(fn ($item) => (float) $item->weight);
            $overdue = $applicable->contains(fn ($item) => in_array($item->status, ['pending', 'in_progress'], true) && $item->due_at && CarbonImmutable::parse($item->due_at)->isPast())
                || $groupRevisions->contains(fn ($item) => ! in_array($item->status, ['resolved', 'cancelled'], true) && $item->due_at && CarbonImmutable::parse($item->due_at)->isPast());
            $completed = $groupMilestones->isNotEmpty() && $groupMilestones->every(fn ($item) => in_array($item->status, ['completed', 'not_applicable'], true));
            $current = $groupMilestones->first(fn ($item) => ! in_array($item->status, ['completed', 'not_applicable'], true));

            $stageCode = $current?->code;
            $stageName = $current?->name ?? ($completed ? 'Completed' : 'Not configured');
            $status = $overdue ? 'overdue' : ($completed ? 'completed' : 'active');
            $completion = $applicableWeight > 0 ? round(($completedWeight / $applicableWeight) * 100, 2) : 0.0;

            if ($report === 'research-stage-status') {
                if ($filters->stage && $stageCode !== $filters->stage) {
                    continue;
                }
                if ($filters->status && $status !== $filters->status) {
                    continue;
                }
                $rows[] = [
                    'Group' => $group->name,
                    'Class' => $group->class_name,
                    'Program' => $group->program_name,
                    'Adviser' => $group->adviser_name ?? 'Unassigned',
                    'Current Stage' => $stageName,
                    'Status' => str($status)->headline()->toString(),
                ];
            } elseif ($report === 'milestone-completion') {
                $rows[] = [
                    'Group' => $group->name,
                    'Class' => $group->class_name,
                    'Program' => $group->program_name,
                    'Applicable Weight' => round($applicableWeight, 4),
                    'Completed Weight' => round($completedWeight, 4),
                    'Completion' => number_format($completion, 2).'%',
                ];
            }
        }

        return $rows;
    }

    private function baseGroups(ReportScope $scope, ReportFilters $filters): Builder
    {
        $query = DB::table('research_class_groups as rcg')
            ->join('research_classes as rc', 'rc.id', '=', 'rcg.research_class_id')
            ->join('research_groups as rg', 'rg.id', '=', 'rcg.research_group_id')
            ->join('programs as p', 'p.id', '=', 'rg.program_id')
            ->join('departments as dep', 'dep.id', '=', 'p.department_id')
            ->join('colleges as c', 'c.id', '=', 'dep.college_id')
            ->join('academic_terms as at', 'at.id', '=', 'rg.academic_term_id')
            ->join('academic_years as ay', 'ay.id', '=', 'at.academic_year_id')
            ->leftJoin('users as u', 'u.id', '=', 'rcg.adviser_id')
            ->where('c.code', config('academic.college.code', 'CEAC'));

        if ($scope->isFacilitator()) {
            $query->whereIn('rc.id', $scope->researchClassIds ?: [-1]);
        }

        return $query
            ->when($filters->academicYearId, fn ($q, $id) => $q->where('ay.id', $id))
            ->when($filters->academicTermId, fn ($q, $id) => $q->where('at.id', $id))
            ->when($filters->programId, fn ($q, $id) => $q->where('p.id', $id))
            ->when($filters->researchClassId, fn ($q, $id) => $q->where('rc.id', $id))
            ->when($filters->adviserId, fn ($q, $id) => $q->where('rcg.adviser_id', $id))
            ->when($filters->dateFrom, fn ($q, $date) => $q->whereDate('rcg.created_at', '>=', $date))
            ->when($filters->dateTo, fn ($q, $date) => $q->whereDate('rcg.created_at', '<=', $date));
    }

    private function validateScope(ReportScope $scope, ReportFilters $filters): void
    {
        $errors = [];
        if ($filters->researchClassId && $scope->isFacilitator() && ! in_array($filters->researchClassId, $scope->researchClassIds, true)) {
            $errors['research_class_id'] = 'The selected research class is outside your authorized scope.';
        }
        if ($filters->programId && ! DB::table('programs as p')->join('departments as d', 'd.id', '=', 'p.department_id')->join('colleges as c', 'c.id', '=', 'd.college_id')->where('p.id', $filters->programId)->where('c.code', config('academic.college.code', 'CEAC'))->exists()) {
            $errors['program_id'] = 'The selected program is outside CEAC scope.';
        }
        if ($filters->academicYearId && $filters->academicTermId && ! DB::table('academic_terms')->where('id', $filters->academicTermId)->where('academic_year_id', $filters->academicYearId)->exists()) {
            $errors['academic_term_id'] = 'The selected academic term does not belong to the selected academic year.';
        }
        if ($filters->adviserId && ! $this->baseGroups($scope, new ReportFilters(adviserId: $filters->adviserId))->exists()) {
            $errors['adviser_id'] = 'The selected adviser is outside your authorized scope.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function statusSummary(ReportScope $scope, ReportFilters $filters): array
    {
        $allGroups = $this->getDetailRows('research-stage-status', $scope, $filters);
        $counts = collect($allGroups)->countBy('Status');

        $rows = [
            ['Status' => 'Active', 'Groups' => (int) ($counts->get('Active', 0))],
            ['Status' => 'Completed', 'Groups' => (int) ($counts->get('Completed', 0))],
            ['Status' => 'Overdue', 'Groups' => (int) ($counts->get('Overdue', 0))],
            ['Status' => 'Delayed (Unavailable)', 'Groups' => 0],
        ];

        return $this->result(collect($rows), ['definition' => 'Overdue requires an incomplete milestone or unresolved revision with a past due date. Delayed is unavailable in the current schema.']);
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function outputByProgram(ReportScope $scope, ReportFilters $filters): array
    {
        $allGroups = $this->getDetailRows('research-stage-status', $scope, $filters);
        $grouped = collect($allGroups)->groupBy('Program');

        $rows = $grouped->map(fn ($items, $program) => [
            'Program' => $program,
            'Research Groups' => $items->count(),
            'Completed' => $items->where('Status', 'Completed')->count(),
            'Overdue' => $items->where('Status', 'Overdue')->count(),
        ])->values();

        return $this->result($rows);
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function throughput(ReportScope $scope, ReportFilters $filters): array
    {
        $query = DB::table('research_group_milestone_events as e')
            ->join('research_group_milestones as gm', 'gm.id', '=', 'e.research_group_milestone_id')
            ->join('research_class_groups as rcg', 'rcg.id', '=', 'gm.research_class_group_id')
            ->join('research_classes as rc', 'rc.id', '=', 'rcg.research_class_id')
            ->join('research_groups as rg', 'rg.id', '=', 'rcg.research_group_id')
            ->join('programs as p', 'p.id', '=', 'rg.program_id')
            ->join('departments as dep', 'dep.id', '=', 'p.department_id')
            ->join('colleges as c', 'c.id', '=', 'dep.college_id')
            ->join('academic_terms as at', 'at.id', '=', 'rg.academic_term_id')
            ->join('academic_years as ay', 'ay.id', '=', 'at.academic_year_id')
            ->where('c.code', config('academic.college.code', 'CEAC'))
            ->where('e.to_status', 'completed');

        if ($scope->isFacilitator()) {
            $query->whereIn('rc.id', $scope->researchClassIds ?: [-1]);
        }

        $events = $query
            ->when($filters->academicYearId, fn ($q, $id) => $q->where('ay.id', $id))
            ->when($filters->academicTermId, fn ($q, $id) => $q->where('at.id', $id))
            ->when($filters->programId, fn ($q, $id) => $q->where('p.id', $id))
            ->when($filters->researchClassId, fn ($q, $id) => $q->where('rc.id', $id))
            ->when($filters->adviserId, fn ($q, $id) => $q->where('rcg.adviser_id', $id))
            ->when($filters->dateFrom, fn ($q, $d) => $q->whereDate('e.occurred_at', '>=', $d))
            ->when($filters->dateTo, fn ($q, $d) => $q->whereDate('e.occurred_at', '<=', $d))
            ->pluck('e.occurred_at');

        $rows = $events->map(fn ($date) => CarbonImmutable::parse($date)->format('Y-m'))
            ->countBy()
            ->sortKeys()
            ->map(fn ($count, $month) => ['Month' => $month, 'Completed Milestones' => $count])
            ->values();

        return $this->result($rows, ['definition' => 'Throughput counts verified milestone transitions to completed.']);
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function defenseTypes(ReportScope $scope, ReportFilters $filters): array
    {
        $items = $this->domainRows('defenses', $scope, $filters, ['defense_type', 'status', 'created_at']);

        $rows = collect(['title_presentation', 'proposal_defense', 'pre_final_defense', 'final_defense'])->map(function ($type) use ($items) {
            $set = $items->where('defense_type', $type);

            return [
                'Defense Type' => str($type)->headline()->toString(),
                'Draft' => $set->where('status', 'draft')->count(),
                'Scheduled' => $set->where('status', 'scheduled')->count(),
                'Completed' => $set->where('status', 'completed')->count(),
                'Cancelled' => $set->where('status', 'cancelled')->count(),
            ];
        });

        return $this->result($rows);
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function defenseStatuses(ReportScope $scope, ReportFilters $filters): array
    {
        $counts = $this->domainRows('defenses', $scope, $filters, ['status', 'created_at'])->countBy('status');

        $rows = collect(['draft', 'scheduled', 'completed', 'cancelled'])->map(fn ($status) => [
            'Status' => $status === 'draft' ? 'Pending' : str($status)->headline()->toString(),
            'Defenses' => (int) $counts->get($status, 0),
        ]);

        return $this->result($rows);
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function adviserWorkload(ReportScope $scope, ReportFilters $filters): array
    {
        $allGroups = $this->getDetailRows('research-stage-status', $scope, $filters);
        $activeGroups = collect($allGroups)->where('Adviser', '!=', 'Unassigned')->where('Status', '!=', 'Completed');
        $grouped = $activeGroups->groupBy('Adviser');

        $rows = $grouped->map(fn ($items, $adviser) => [
            'Adviser' => $adviser,
            'Active Groups' => $items->count(),
        ])->sortByDesc('Active Groups')->values();

        return $this->result($rows, ['definition' => 'Workload counts current adviser assignments for groups that are not completed.']);
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function documentReviewStatus(ReportScope $scope, ReportFilters $filters): array
    {
        $query = DB::table('documents as d')
            ->join('research_class_groups as rcg', 'rcg.id', '=', 'd.research_class_group_id')
            ->join('research_classes as rc', 'rc.id', '=', 'rcg.research_class_id')
            ->join('research_groups as rg', 'rg.id', '=', 'rcg.research_group_id')
            ->join('programs as p', 'p.id', '=', 'rg.program_id')
            ->join('departments as dep', 'dep.id', '=', 'p.department_id')
            ->join('colleges as c', 'c.id', '=', 'dep.college_id')
            ->join('academic_terms as at', 'at.id', '=', 'rg.academic_term_id')
            ->join('academic_years as ay', 'ay.id', '=', 'at.academic_year_id')
            ->leftJoin('document_reviews as dr', fn ($join) => $join->on('dr.document_id', '=', 'd.id')->where('dr.is_superseded', false))
            ->where('c.code', config('academic.college.code', 'CEAC'))
            ->where('d.is_current', true);

        if ($scope->isFacilitator()) {
            $query->whereIn('rc.id', $scope->researchClassIds ?: [-1]);
        }

        $items = $query
            ->when($filters->academicYearId, fn ($q, $id) => $q->where('ay.id', $id))
            ->when($filters->academicTermId, fn ($q, $id) => $q->where('at.id', $id))
            ->when($filters->programId, fn ($q, $id) => $q->where('p.id', $id))
            ->when($filters->researchClassId, fn ($q, $id) => $q->where('rc.id', $id))
            ->when($filters->adviserId, fn ($q, $id) => $q->where('rcg.adviser_id', $id))
            ->when($filters->dateFrom, fn ($q, $d) => $q->whereDate('d.submitted_at', '>=', $d))
            ->when($filters->dateTo, fn ($q, $d) => $q->whereDate('d.submitted_at', '<=', $d))
            ->select(['d.id', 'd.status', 'dr.id as review_id'])
            ->get();

        $rows = $items->groupBy('status')->map(fn ($set, $status) => [
            'Document Status' => str($status)->headline()->toString(),
            'Documents' => $set->unique('id')->count(),
            'Reviewed' => $set->whereNotNull('review_id')->unique('id')->count(),
            'Awaiting Review' => $set->whereNull('review_id')->unique('id')->count(),
        ])->values();

        return $this->result($rows);
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function revisionSummary(ReportScope $scope, ReportFilters $filters): array
    {
        $items = $this->domainRows('revision_requests', $scope, $filters, ['status', 'due_at', 'created_at']);

        $rows = $items->groupBy('status')->map(fn ($set, $status) => [
            'Status' => str($status)->headline()->toString(),
            'Requests' => $set->count(),
            'Overdue' => $set->filter(fn ($item) => ! in_array($item->status, ['resolved', 'cancelled'], true) && $item->due_at && CarbonImmutable::parse($item->due_at)->isPast())->count(),
        ])->values();

        return $this->result($rows);
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function evaluationRelease(ReportScope $scope, ReportFilters $filters): array
    {
        $items = $this->domainRows('defense_evaluation_rounds', $scope, $filters, ['released_at', 'created_at']);
        $released = $items->whereNotNull('released_at')->count();

        $rows = collect([
            ['Release Status' => 'Released', 'Evaluation Rounds' => $released],
            ['Release Status' => 'Not Released', 'Evaluation Rounds' => $items->count() - $released],
        ]);

        return $this->result($rows);
    }

    /** @param list<string> $columns @return Collection<int,object> */
    private function domainRows(string $table, ReportScope $scope, ReportFilters $filters, array $columns): Collection
    {
        $query = DB::table("{$table} as t")
            ->join('research_class_groups as rcg', 'rcg.id', '=', 't.research_class_group_id')
            ->join('research_classes as rc', 'rc.id', '=', 'rcg.research_class_id')
            ->join('research_groups as rg', 'rg.id', '=', 'rcg.research_group_id')
            ->join('programs as p', 'p.id', '=', 'rg.program_id')
            ->join('departments as dep', 'dep.id', '=', 'p.department_id')
            ->join('colleges as c', 'c.id', '=', 'dep.college_id')
            ->join('academic_terms as at', 'at.id', '=', 'rg.academic_term_id')
            ->join('academic_years as ay', 'ay.id', '=', 'at.academic_year_id')
            ->where('c.code', config('academic.college.code', 'CEAC'));

        if ($scope->isFacilitator()) {
            $query->whereIn('rc.id', $scope->researchClassIds ?: [-1]);
        }

        $selectColumns = array_map(fn ($c) => "t.{$c}", $columns);

        return $query
            ->when($filters->academicYearId, fn ($q, $id) => $q->where('ay.id', $id))
            ->when($filters->academicTermId, fn ($q, $id) => $q->where('at.id', $id))
            ->when($filters->programId, fn ($q, $id) => $q->where('p.id', $id))
            ->when($filters->researchClassId, fn ($q, $id) => $q->where('rc.id', $id))
            ->when($filters->adviserId, fn ($q, $id) => $q->where('rcg.adviser_id', $id))
            ->when($filters->dateFrom, fn ($q, $d) => $q->whereDate('t.created_at', '>=', $d))
            ->when($filters->dateTo, fn ($q, $d) => $q->whereDate('t.created_at', '<=', $d))
            ->select($selectColumns)
            ->get();
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function result(Collection $rows, array $summary = []): array
    {
        return ['rows' => $rows->values()->all(), 'summary' => ['row_count' => $rows->count()] + $summary];
    }
}
