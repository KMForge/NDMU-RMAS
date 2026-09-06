<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Modules\DefenseScheduling\Actions\BulkScheduleDefenses;
use App\Modules\DefenseScheduling\Services\CheckDefenseSchedulingConflicts;
use App\Modules\DefenseScheduling\Services\DefenseEndorsementEligibility;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BulkDefenseController extends Controller
{
    public function checkConflicts(
        Request $request,
        CheckDefenseSchedulingConflicts $conflictChecker,
        DefenseEndorsementEligibility $endorsementEligibility,
    ): JsonResponse {
        $validated = $request->validate([
            'research_class_id' => ['required', 'integer', 'exists:research_classes,id'],
            'defense_type' => ['required', 'string', 'in:title_presentation,proposal_defense,pre_final_defense,final_defense'],
            'room_id' => ['required', 'integer', 'exists:defense_rooms,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'ordered_group_ids' => ['required', 'array', 'min:1'],
            'ordered_group_ids.*' => ['integer', 'exists:research_class_groups,id'],
        ]);

        $researchClass = ResearchClass::findOrFail($validated['research_class_id']);

        try {
            $this->ensureGroupEndorsements(
                $researchClass,
                array_map('intval', $validated['ordered_group_ids']),
                $validated['defense_type'],
                $endorsementEligibility,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $result = $conflictChecker->check(
            $researchClass,
            $validated['defense_type'],
            (int) $validated['room_id'],
            Carbon::parse($validated['starts_at']),
            Carbon::parse($validated['ends_at']),
            array_map('intval', $validated['ordered_group_ids']),
        );

        return response()->json($result);
    }

    public function store(
        Request $request,
        BulkScheduleDefenses $bulkSchedule,
        DefenseEndorsementEligibility $endorsementEligibility,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'research_class_id' => ['required', 'integer', 'exists:research_classes,id'],
            'defense_type' => ['required', 'string', 'in:title_presentation,proposal_defense,pre_final_defense,final_defense'],
            'room_id' => ['required', 'integer', 'exists:defense_rooms,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'ordered_group_ids' => ['required', 'array', 'min:1'],
            'ordered_group_ids.*' => ['integer', 'exists:research_class_groups,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $researchClass = ResearchClass::findOrFail($validated['research_class_id']);

        try {
            $this->ensureGroupEndorsements(
                $researchClass,
                array_map('intval', $validated['ordered_group_ids']),
                $validated['defense_type'],
                $endorsementEligibility,
            );

            $session = $bulkSchedule->handle(
                $request->user(),
                $researchClass,
                $validated['defense_type'],
                (int) $validated['room_id'],
                Carbon::parse($validated['starts_at']),
                Carbon::parse($validated['ends_at']),
                array_map('intval', $validated['ordered_group_ids']),
                $validated['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return to_route('facilitator.dashboard', ['tab' => 'defenses'])
                ->withErrors(['bulk_defense' => $e->getMessage()])
                ->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bulk defense schedule created successfully.',
                'session' => $session,
            ], 201);
        }

        return to_route('facilitator.dashboard', ['tab' => 'defenses'])
            ->with('status', 'Bulk defense schedule created successfully for '.count($validated['ordered_group_ids']).' groups.');
    }

    /** @param list<int> $groupIds */
    private function ensureGroupEndorsements(
        ResearchClass $researchClass,
        array $groupIds,
        string $defenseType,
        DefenseEndorsementEligibility $endorsementEligibility,
    ): void {
        $groups = ResearchClassGroup::query()
            ->where('research_class_id', $researchClass->id)
            ->whereIn('id', $groupIds)
            ->get()
            ->keyBy('id');

        foreach ($groupIds as $groupId) {
            $group = $groups->get($groupId);
            if ($group !== null) {
                $endorsementEligibility->ensureComplete($group, $defenseType);
            }
        }
    }
}
