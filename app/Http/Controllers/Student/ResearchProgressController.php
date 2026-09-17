<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ResearchClassGroup;
use App\Modules\ResearchProgress\Queries\GetResearchGroupProgress;
use App\Modules\ResearchProgress\Support\ResearchProgressAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ResearchProgressController extends Controller
{
    public function show(ResearchClassGroup $group, GetResearchGroupProgress $query, ResearchProgressAccess $access): JsonResponse
    {
        abort_unless($access->canView(request()->user(), $group), 403);
        $data = $query->for($group, request()->user());
        Gate::authorize('view', $data['milestones']->firstOrFail());

        return response()->json([
            'group' => ['id' => $group->getKey(), 'name' => $group->name],
            'progress_percentage' => $data['progress_percentage'],
            'milestones' => $data['milestones']->map(fn ($milestone) => [
                'name' => $milestone->definition->name,
                'sequence' => $milestone->definition->sequence,
                'status' => $milestone->status->value,
                'derived_status' => data_get($data, 'journey.stages.'.$milestone->definition->sequence.'.is_completed')
                    ? 'completed'
                    : $milestone->status->value,
                'is_auto_completed' => (bool) data_get($data, 'journey.stages.'.$milestone->definition->sequence.'.is_auto_completed', false),
                'due_at' => $milestone->due_at?->toAtomString(),
                'is_overdue' => $milestone->isOverdue(),
                'remarks' => $milestone->remarks,
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
        ]);
    }
}
