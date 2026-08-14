<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Models\Defense;
use App\Models\ResearchClassGroup;
use App\Modules\DefenseScheduling\Actions\AssignDefensePanel;
use App\Modules\DefenseScheduling\Actions\CancelDefense;
use App\Modules\DefenseScheduling\Actions\RescheduleDefense;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DefenseController extends Controller
{
    public function store(Request $request, ScheduleDefense $scheduleDefense): RedirectResponse
    {
        $validated = $request->validate([
            'research_class_group_id' => ['required', 'integer', 'exists:research_class_groups,id'],
            'defense_type' => ['required', 'string', 'in:proposal_defense,final_defense'],
            'room_id' => ['required', 'integer', 'exists:defense_rooms,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'panel_user_ids' => ['required', 'array', 'distinct', 'min:1'],
            'panel_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $group = ResearchClassGroup::findOrFail($validated['research_class_group_id']);

        $scheduleDefense->handle(
            $request->user(),
            $group,
            $validated['defense_type'],
            (int) $validated['room_id'],
            Carbon::parse($validated['starts_at']),
            Carbon::parse($validated['ends_at']),
            array_map('intval', $validated['panel_user_ids'])
        );

        return to_route('facilitator.dashboard', ['tab' => 'defenses'])
            ->with('status', 'Defense scheduled successfully.');
    }

    public function reschedule(Request $request, Defense $defense, RescheduleDefense $rescheduleDefense): RedirectResponse
    {
        $validated = $request->validate([
            'expected_current_schedule_id' => ['required', 'integer'],
            'room_id' => ['required', 'integer', 'exists:defense_rooms,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $rescheduleDefense->handle(
            $request->user(),
            $defense,
            (int) $validated['expected_current_schedule_id'],
            (int) $validated['room_id'],
            Carbon::parse($validated['starts_at']),
            Carbon::parse($validated['ends_at']),
            $validated['reason']
        );

        return to_route('facilitator.dashboard', ['tab' => 'defenses'])
            ->with('status', 'Defense rescheduled successfully.');
    }

    public function cancel(Request $request, Defense $defense, CancelDefense $cancelDefense): RedirectResponse
    {
        $validated = $request->validate([
            'expected_current_schedule_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $cancelDefense->handle(
            $request->user(),
            $defense,
            (int) $validated['expected_current_schedule_id'],
            $validated['reason']
        );

        return to_route('facilitator.dashboard', ['tab' => 'defenses'])
            ->with('status', 'Defense cancelled successfully.');
    }

    public function assignPanel(Request $request, Defense $defense, AssignDefensePanel $assignDefensePanel): RedirectResponse
    {
        $validated = $request->validate([
            'panel_user_ids' => ['required', 'array', 'distinct'],
            'panel_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $assignDefensePanel->handle(
            $request->user(),
            $defense,
            array_map('intval', $validated['panel_user_ids'])
        );

        return to_route('facilitator.dashboard', ['tab' => 'defenses'])
            ->with('status', 'Defense panel assigned successfully.');
    }
}
