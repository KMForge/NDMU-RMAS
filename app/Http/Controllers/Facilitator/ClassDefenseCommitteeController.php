<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Modules\DefenseScheduling\Actions\AssignClassDefenseCommittee;
use App\Modules\DefenseScheduling\Actions\AssignGroupDefenseCommittee;
use App\Modules\DefenseScheduling\Actions\ResetGroupDefenseCommittee;
use App\Modules\DefenseScheduling\Queries\GetClassCommitteeAssignments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ClassDefenseCommitteeController extends Controller
{
    public function show(
        Request $request,
        ResearchClass $researchClass,
        GetClassCommitteeAssignments $query,
    ): JsonResponse {
        $defenseType = (string) $request->query('defense_type', 'proposal_defense');

        $data = $query->forClass($researchClass, $defenseType);

        return response()->json($data);
    }

    public function assignClass(
        Request $request,
        ResearchClass $researchClass,
        AssignClassDefenseCommittee $action,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'defense_type' => ['required', 'string', 'in:title_presentation,proposal_defense,pre_final_defense,final_defense'],
            'chairperson_user_id' => ['required', 'integer', 'exists:users,id'],
            'panel_user_ids' => ['required', 'array', 'size:2', 'distinct'],
            'panel_user_ids.*' => ['integer', 'exists:users,id'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:research_class_groups,id'],
            'override_custom' => ['nullable', 'boolean'],
        ]);

        try {
            $committee = $action->handle(
                $request->user(),
                $researchClass,
                $validated['defense_type'],
                (int) $validated['chairperson_user_id'],
                $validated['panel_user_ids'],
                $validated['group_ids'] ?? [],
                (bool) ($validated['override_custom'] ?? false),
            );
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['defense_committee' => $e->getMessage()])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Class defense committee successfully assigned.',
                'committee' => $committee,
            ]);
        }

        return back()->with('status', 'Class defense committee successfully assigned.');
    }

    public function assignGroup(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        AssignGroupDefenseCommittee $action,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'defense_type' => ['required', 'string', 'in:title_presentation,proposal_defense,pre_final_defense,final_defense'],
            'chairperson_user_id' => ['required', 'integer', 'exists:users,id'],
            'panel_user_ids' => ['required', 'array', 'size:2', 'distinct'],
            'panel_user_ids.*' => ['integer', 'exists:users,id'],
            'is_custom' => ['nullable', 'boolean'],
        ]);

        try {
            $committee = $action->handle(
                $request->user(),
                $group,
                $validated['defense_type'],
                (int) $validated['chairperson_user_id'],
                $validated['panel_user_ids'],
                $validated['is_custom'] ?? true,
            );
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['group_defense_committee' => $e->getMessage()])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Group defense committee successfully updated.',
                'committee' => $committee,
            ]);
        }

        return back()->with('status', 'Group defense committee successfully updated.');
    }

    public function resetGroup(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassGroup $group,
        ResetGroupDefenseCommittee $action,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'defense_type' => ['required', 'string', 'in:title_presentation,proposal_defense,pre_final_defense,final_defense'],
        ]);

        try {
            $committee = $action->handle($request->user(), $group, $validated['defense_type']);
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['group_defense_committee' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Group committee reverted to class default.',
                'committee' => $committee,
            ]);
        }

        return back()->with('status', 'Group committee reverted to class default.');
    }
}
