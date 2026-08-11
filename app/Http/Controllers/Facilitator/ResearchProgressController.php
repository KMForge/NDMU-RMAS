<?php

namespace App\Http\Controllers\Facilitator;

use App\Enums\ResearchMilestoneStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResearchProgress\CompleteMilestoneRequest;
use App\Http\Requests\ResearchProgress\CorrectMilestoneRequest;
use App\Http\Requests\ResearchProgress\LinkMilestoneEvidenceRequest;
use App\Http\Requests\ResearchProgress\NotApplicableMilestoneRequest;
use App\Http\Requests\ResearchProgress\StartMilestoneRequest;
use App\Http\Requests\ResearchProgress\UpdateMilestoneDueDateRequest;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use App\Modules\ResearchProgress\Actions\LinkMilestoneEvidence;
use App\Modules\ResearchProgress\Actions\TransitionResearchGroupMilestone;
use App\Modules\ResearchProgress\Actions\UpdateMilestoneDueDate;
use App\Modules\ResearchProgress\Queries\GetResearchGroupProgress;
use App\Modules\ResearchProgress\Support\ResearchProgressAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResearchProgressController extends Controller
{
    public function show(Request $request, ResearchClassGroup $group, GetResearchGroupProgress $query, ResearchProgressAccess $access): JsonResponse
    {
        abort_unless($access->canView($request->user(), $group), 403);
        $data = $query->for($group);
        $first = $data['milestones']->firstOrFail();
        Gate::authorize('view', $first);

        return response()->json($this->safe($data));
    }

    public function start(StartMilestoneRequest $request, ResearchGroupMilestone $milestone, TransitionResearchGroupMilestone $action): RedirectResponse|JsonResponse
    {
        return $this->transition($request, $milestone, $action, ResearchMilestoneStatus::InProgress);
    }

    public function complete(CompleteMilestoneRequest $request, ResearchGroupMilestone $milestone, TransitionResearchGroupMilestone $action): RedirectResponse|JsonResponse
    {
        return $this->transition($request, $milestone, $action, ResearchMilestoneStatus::Completed);
    }

    public function correct(CorrectMilestoneRequest $request, ResearchGroupMilestone $milestone, TransitionResearchGroupMilestone $action): RedirectResponse|JsonResponse
    {
        return $this->transition($request, $milestone, $action, ResearchMilestoneStatus::from($request->string('status')->toString()));
    }

    public function notApplicable(NotApplicableMilestoneRequest $request, ResearchGroupMilestone $milestone, TransitionResearchGroupMilestone $action): RedirectResponse|JsonResponse
    {
        return $this->transition($request, $milestone, $action, ResearchMilestoneStatus::NotApplicable);
    }

    public function dueDate(UpdateMilestoneDueDateRequest $request, ResearchGroupMilestone $milestone, UpdateMilestoneDueDate $action): RedirectResponse|JsonResponse
    {
        Gate::authorize('manage', $milestone);
        $updated = $action->execute($request->user(), $milestone, $request->validated('due_at'), $request->validated('reason'), $request->ip());

        return $this->respond($request, 'Milestone due date updated.', $updated);
    }

    public function evidence(LinkMilestoneEvidenceRequest $request, ResearchGroupMilestone $milestone, LinkMilestoneEvidence $action): RedirectResponse|JsonResponse
    {
        Gate::authorize('manage', $milestone);
        $link = $action->execute(
            $request->user(), $milestone, $request->validated('evidence_type'),
            (int) $request->validated('evidence_id'), $request->validated('summary'), $request->ip(),
        );

        return $this->respond($request, 'Milestone evidence linked.', $link);
    }

    private function transition(Request $request, ResearchGroupMilestone $milestone, TransitionResearchGroupMilestone $action, ResearchMilestoneStatus $target): RedirectResponse|JsonResponse
    {
        Gate::authorize('manage', $milestone);
        $updated = $action->execute(
            $request->user(), $milestone, $target, $request->input('reason'), $request->input('remarks'),
            $request->boolean('override_order'), $request->boolean('direct_completion'), $request->ip(),
        );

        return $this->respond($request, 'Milestone status updated.', $updated);
    }

    private function respond(Request $request, string $message, mixed $resource): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => $resource]);
        }

        return to_route('facilitator.dashboard', ['tab' => 'monitoring'])->with('success', $message);
    }

    /** @param array<string, mixed> $data */
    private function safe(array $data): array
    {
        return [
            'group' => ['id' => $data['group']->getKey(), 'name' => $data['group']->name, 'status' => $data['group']->status],
            'progress_percentage' => $data['progress_percentage'], 'completed_count' => $data['completed_count'],
            'applicable_count' => $data['applicable_count'],
            'milestones' => $data['milestones']->map(fn ($milestone) => [
                'id' => $milestone->getKey(), 'name' => $milestone->definition->name,
                'sequence' => $milestone->definition->sequence, 'weight' => (float) $milestone->definition->weight,
                'status' => $milestone->status->value, 'due_at' => $milestone->due_at?->toAtomString(),
                'is_overdue' => $milestone->isOverdue(), 'remarks' => $milestone->remarks,
                'evidence' => $milestone->evidences->map(fn ($evidence) => [
                    'type' => $evidence->evidence_type, 'summary' => $evidence->summary,
                    'linked_at' => $evidence->linked_at?->toAtomString(),
                ])->values(),
                'history' => $milestone->events->map(fn ($event) => [
                    'event' => $event->event, 'from_status' => $event->from_status?->value,
                    'to_status' => $event->to_status?->value, 'reason' => $event->reason,
                    'actor' => $event->actor?->name, 'occurred_at' => $event->occurred_at?->toAtomString(),
                ])->values(),
            ])->values(),
        ];
    }
}
