<?php

namespace App\Modules\ReportsAnalytics\Queries;

use App\Modules\ReportsAnalytics\ReportCatalog;
use App\Modules\ReportsAnalytics\ValueObjects\ReportFilters;
use App\Modules\ReportsAnalytics\ValueObjects\ReportScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RunReport
{
    /** @return array{columns:list<string>,rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    public function execute(string $report, ReportScope $scope, ReportFilters $filters): array
    {
        $definition = app(ReportCatalog::class)->get($report);
        $this->validateScope($scope, $filters);

        $key = implode(':', ['reports', $definition['version'], $report, $scope->fingerprint(), $filters->fingerprint()]);

        return Cache::remember($key, max(1, (int) config('analytics.cache_ttl', 900)), function () use ($report, $scope, $filters, $definition): array {
            $dateAppliesToGroupCreation = in_array($report, ['research-stage-status', 'research-summary', 'milestone-completion', 'research-output-program', 'adviser-workload'], true);
            $groupFilters = $dateAppliesToGroupCreation ? $filters : new ReportFilters(
                academicYearId: $filters->academicYearId, academicTermId: $filters->academicTermId,
                programId: $filters->programId, researchClassId: $filters->researchClassId,
                adviserId: $filters->adviserId, stage: $filters->stage, status: $filters->status,
            );
            $groups = $this->groupSnapshots($scope, $groupFilters);
            $handlers = [
                'research-stage-status' => fn () => $this->stageStatus($groups, $filters),
                'research-summary' => fn () => $this->statusSummary($groups),
                'milestone-completion' => fn () => $this->milestoneCompletion($groups),
                'research-output-program' => fn () => $this->outputByProgram($groups),
                'research-throughput' => fn () => $this->throughput($groups, $filters),
                'defense-types' => fn () => $this->defenseTypes($groups, $filters),
                'defense-status' => fn () => $this->defenseStatuses($groups, $filters),
                'adviser-workload' => fn () => $this->adviserWorkload($groups),
                'document-review-status' => fn () => $this->documentReviewStatus($groups, $filters),
                'revision-summary' => fn () => $this->revisionSummary($groups, $filters),
                'evaluation-release-status' => fn () => $this->evaluationRelease($groups, $filters),
            ];

            abort_unless(isset($handlers[$report]), 404);

            return ['columns' => $definition['columns']] + $handlers[$report]();
        });
    }

    /** @return Collection<int, object> */
    public function filterOptions(ReportScope $scope): Collection
    {
        return $this->baseGroups($scope, new ReportFilters)
            ->select(['rcg.id', 'rcg.research_class_id', 'rc.name as class_name', 'p.id as program_id', 'p.name as program_name', 'u.id as adviser_id', 'u.name as adviser_name', 'at.id as term_id', 'at.name as term_name', 'ay.id as year_id', 'ay.name as year_name'])
            ->orderBy('rcg.name')->get();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function groupSnapshots(ReportScope $scope, ReportFilters $filters): Collection
    {
        $limit = max(1, (int) config('analytics.max_export_rows', 10000)) + 1;
        $groups = $this->baseGroups($scope, $filters)
            ->select(['rcg.id', 'rcg.name', 'rcg.status as group_status', 'rcg.research_class_id', 'rc.name as class_name', 'rcg.adviser_id', 'u.name as adviser_name', 'p.id as program_id', 'p.name as program_name', 'at.id as term_id', 'at.name as term_name', 'ay.id as year_id', 'ay.name as year_name', 'rcg.created_at'])
            ->orderBy('rcg.id')->limit($limit)->get();

        $ids = $groups->pluck('id');
        if ($ids->isEmpty()) {
            return collect();
        }

        $milestones = DB::table('research_group_milestones as gm')
            ->join('milestone_definitions as md', 'md.id', '=', 'gm.milestone_definition_id')
            ->whereIn('gm.research_class_group_id', $ids)
            ->select(['gm.research_class_group_id', 'gm.id', 'gm.status', 'gm.due_at', 'gm.completed_at', 'md.code', 'md.name', 'md.sequence', 'md.weight'])
            ->orderBy('md.sequence')->get()->groupBy('research_class_group_id');

        $revisions = DB::table('revision_requests')->whereIn('research_class_group_id', $ids)
            ->select(['id', 'research_class_group_id', 'status', 'due_at', 'resolved_at', 'created_at'])->get()->groupBy('research_class_group_id');

        return $groups->map(function ($group) use ($milestones, $revisions): array {
            $groupMilestones = collect($milestones->get($group->id, []));
            $groupRevisions = collect($revisions->get($group->id, []));
            $applicable = $groupMilestones->where('status', '!=', 'not_applicable');
            $completedWeight = $applicable->where('status', 'completed')->sum(fn ($item) => (float) $item->weight);
            $applicableWeight = $applicable->sum(fn ($item) => (float) $item->weight);
            $overdue = $applicable->contains(fn ($item) => in_array($item->status, ['pending', 'in_progress'], true) && $item->due_at && CarbonImmutable::parse($item->due_at)->isPast())
                || $groupRevisions->contains(fn ($item) => ! in_array($item->status, ['resolved', 'cancelled'], true) && $item->due_at && CarbonImmutable::parse($item->due_at)->isPast());
            $completed = $groupMilestones->isNotEmpty() && $groupMilestones->every(fn ($item) => in_array($item->status, ['completed', 'not_applicable'], true));
            $current = $groupMilestones->first(fn ($item) => ! in_array($item->status, ['completed', 'not_applicable'], true));

            return [
                'id' => (int) $group->id, 'group' => $group->name, 'class' => $group->class_name,
                'research_class_id' => (int) $group->research_class_id, 'program_id' => (int) $group->program_id,
                'program' => $group->program_name, 'adviser_id' => $group->adviser_id ? (int) $group->adviser_id : null,
                'adviser' => $group->adviser_name ?? 'Unassigned', 'stage_code' => $current?->code,
                'stage' => $current?->name ?? ($completed ? 'Completed' : 'Not configured'),
                'status' => $overdue ? 'overdue' : ($completed ? 'completed' : 'active'),
                'milestones' => $groupMilestones, 'revisions' => $groupRevisions,
                'applicable_weight' => round($applicableWeight, 4), 'completed_weight' => round($completedWeight, 4),
                'completion' => $applicableWeight > 0 ? round(($completedWeight / $applicableWeight) * 100, 2) : 0.0,
            ];
        });
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

    /** @return array<string,mixed> */
    private function stageStatus(Collection $groups, ReportFilters $filters): array
    {
        $rows = $groups->when($filters->stage, fn ($items, $stage) => $items->where('stage_code', $stage))->when($filters->status, fn ($items, $status) => $items->where('status', $status));

        return $this->result($rows->map(fn ($g) => ['Group' => $g['group'], 'Class' => $g['class'], 'Program' => $g['program'], 'Adviser' => $g['adviser'], 'Current Stage' => $g['stage'], 'Status' => str($g['status'])->headline()->toString()]));
    }

    private function statusSummary(Collection $groups): array
    {
        $counts = $groups->countBy('status');

        return $this->result(collect(['active', 'completed', 'delayed', 'overdue'])->map(fn ($status) => ['Status' => str($status)->headline()->toString(), 'Groups' => (int) $counts->get($status, 0)]), ['definition' => 'Overdue requires an incomplete milestone or unresolved revision with a past due date. Delayed is unavailable in the current schema.']);
    }

    private function milestoneCompletion(Collection $groups): array
    {
        return $this->result($groups->map(fn ($g) => ['Group' => $g['group'], 'Class' => $g['class'], 'Program' => $g['program'], 'Applicable Weight' => $g['applicable_weight'], 'Completed Weight' => $g['completed_weight'], 'Completion' => number_format($g['completion'], 2).'%']));
    }

    private function outputByProgram(Collection $groups): array
    {
        return $this->result($groups->groupBy('program')->map(fn ($items, $program) => ['Program' => $program, 'Research Groups' => $items->count(), 'Completed' => $items->where('status', 'completed')->count(), 'Overdue' => $items->where('status', 'overdue')->count()])->values());
    }

    private function throughput(Collection $groups, ReportFilters $filters): array
    {
        $ids = $groups->pluck('id');
        $events = $ids->isEmpty() ? collect() : DB::table('research_group_milestone_events as e')->join('research_group_milestones as gm', 'gm.id', '=', 'e.research_group_milestone_id')->whereIn('gm.research_class_group_id', $ids)->where('e.to_status', 'completed')->when($filters->dateFrom, fn ($q, $d) => $q->whereDate('e.occurred_at', '>=', $d))->when($filters->dateTo, fn ($q, $d) => $q->whereDate('e.occurred_at', '<=', $d))->pluck('e.occurred_at');
        $rows = $events->map(fn ($date) => CarbonImmutable::parse($date)->format('Y-m'))->countBy()->sortKeys()->map(fn ($count, $month) => ['Month' => $month, 'Completed Milestones' => $count])->values();

        return $this->result($rows, ['definition' => 'Throughput counts verified milestone transitions to completed.']);
    }

    private function defenseTypes(Collection $groups, ReportFilters $filters): array
    {
        $items = $this->domainRows('defenses', $groups, $filters, ['defense_type', 'status', 'created_at']);

        return $this->result(collect(['title_presentation', 'proposal_defense', 'pre_final_defense', 'final_defense'])->map(function ($type) use ($items) {
            $set = $items->where('defense_type', $type);

            return ['Defense Type' => str($type)->headline()->toString(), 'Draft' => $set->where('status', 'draft')->count(), 'Scheduled' => $set->where('status', 'scheduled')->count(), 'Completed' => $set->where('status', 'completed')->count(), 'Cancelled' => $set->where('status', 'cancelled')->count()];
        }));
    }

    private function defenseStatuses(Collection $groups, ReportFilters $filters): array
    {
        $counts = $this->domainRows('defenses', $groups, $filters, ['status', 'created_at'])->countBy('status');

        return $this->result(collect(['draft', 'scheduled', 'completed', 'cancelled'])->map(fn ($status) => ['Status' => $status === 'draft' ? 'Pending' : str($status)->headline()->toString(), 'Defenses' => (int) $counts->get($status, 0)]));
    }

    private function adviserWorkload(Collection $groups): array
    {
        return $this->result($groups->whereNotNull('adviser_id')->where('status', '!=', 'completed')->groupBy('adviser_id')->map(fn ($items) => ['Adviser' => $items->first()['adviser'], 'Active Groups' => $items->count()])->sortByDesc('Active Groups')->values(), ['definition' => 'Workload counts current adviser assignments for groups that are not completed.']);
    }

    private function documentReviewStatus(Collection $groups, ReportFilters $filters): array
    {
        $ids = $groups->pluck('id');
        $items = $ids->isEmpty() ? collect() : DB::table('documents as d')->leftJoin('document_reviews as dr', fn ($join) => $join->on('dr.document_id', '=', 'd.id')->where('dr.is_superseded', false))->whereIn('d.research_class_group_id', $ids)->where('d.is_current', true)->when($filters->dateFrom, fn ($q, $d) => $q->whereDate('d.submitted_at', '>=', $d))->when($filters->dateTo, fn ($q, $d) => $q->whereDate('d.submitted_at', '<=', $d))->select(['d.id', 'd.status', 'dr.id as review_id'])->get();

        return $this->result($items->groupBy('status')->map(fn ($set, $status) => ['Document Status' => str($status)->headline()->toString(), 'Documents' => $set->unique('id')->count(), 'Reviewed' => $set->whereNotNull('review_id')->unique('id')->count(), 'Awaiting Review' => $set->whereNull('review_id')->unique('id')->count()])->values());
    }

    private function revisionSummary(Collection $groups, ReportFilters $filters): array
    {
        $items = $this->domainRows('revision_requests', $groups, $filters, ['status', 'due_at', 'created_at']);

        return $this->result($items->groupBy('status')->map(fn ($set, $status) => ['Status' => str($status)->headline()->toString(), 'Requests' => $set->count(), 'Overdue' => $set->filter(fn ($item) => ! in_array($item->status, ['resolved', 'cancelled'], true) && $item->due_at && CarbonImmutable::parse($item->due_at)->isPast())->count()])->values());
    }

    private function evaluationRelease(Collection $groups, ReportFilters $filters): array
    {
        $items = $this->domainRows('defense_evaluation_rounds', $groups, $filters, ['released_at', 'created_at']);
        $released = $items->whereNotNull('released_at')->count();

        return $this->result(collect([['Release Status' => 'Released', 'Evaluation Rounds' => $released], ['Release Status' => 'Not Released', 'Evaluation Rounds' => $items->count() - $released]]));
    }

    /** @param list<string> $columns @return Collection<int,object> */
    private function domainRows(string $table, Collection $groups, ReportFilters $filters, array $columns): Collection
    {
        $ids = $groups->pluck('id');
        if ($ids->isEmpty()) {
            return collect();
        }
        $query = DB::table($table)->whereIn('research_class_group_id', $ids)->select($columns);

        return $query->when($filters->dateFrom, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))->when($filters->dateTo, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))->get();
    }

    /** @return array{rows:list<array<string,mixed>>,summary:array<string,mixed>} */
    private function result(Collection $rows, array $summary = []): array
    {
        return ['rows' => $rows->values()->all(), 'summary' => ['row_count' => $rows->count()] + $summary];
    }
}
