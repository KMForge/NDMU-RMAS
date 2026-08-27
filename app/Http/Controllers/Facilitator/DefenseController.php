<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Models\Defense;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Modules\DefenseScheduling\Actions\AssignDefensePanel;
use App\Modules\DefenseScheduling\Actions\CancelDefense;
use App\Modules\DefenseScheduling\Actions\RescheduleDefense;
use App\Modules\DefenseScheduling\Actions\ScheduleDefense;
use App\Modules\TitlePresentations\Actions\AssignTitlePresentationPanel;
use App\Modules\TitlePresentations\Actions\ScheduleTitlePresentation;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DefenseController extends Controller
{
    public function store(
        Request $request,
        ScheduleDefense $scheduleDefense,
        ScheduleTitlePresentation $scheduleTitlePresentation,
        AssignTitlePresentationPanel $assignTitlePresentationPanel,
    ): RedirectResponse {
        $validated = $request->validate([
            'research_class_group_id' => ['required', 'integer', 'exists:research_class_groups,id'],
            'defense_type' => ['required', 'string', 'in:title_presentation,proposal_defense,pre_final_defense,final_defense'],
            'room_id' => ['required', 'integer', 'exists:defense_rooms,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'chairperson_user_id' => ['required', 'integer', 'exists:users,id'],
            'panel_user_ids' => ['required', 'array', 'size:2', 'distinct'],
            'panel_user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $group = ResearchClassGroup::findOrFail($validated['research_class_group_id']);
        $chairpersonId = (int) $validated['chairperson_user_id'];
        $panelUserIds = array_map('intval', $validated['panel_user_ids']);

        if (in_array($chairpersonId, $panelUserIds, true)) {
            throw ValidationException::withMessages([
                'chairperson_user_id' => 'The Chairperson and two Panel Members must be three different Faculty users.',
            ]);
        }

        try {
            if ($validated['defense_type'] === 'title_presentation') {
                $instance = OfficialFormInstance::query()
                    ->where('research_class_group_id', $group->id)
                    ->where('status', 'submitted')
                    ->whereHas('definition', fn ($query) => $query->where('code', 'RES-026'))
                    ->latest('updated_at')
                    ->first();

                if ($instance === null) {
                    throw new InvalidArgumentException('This group must submit RES-026 before its Title Presentation can be scheduled.');
                }

                DB::transaction(function () use (
                    $request,
                    $instance,
                    $validated,
                    $chairpersonId,
                    $panelUserIds,
                    $scheduleTitlePresentation,
                    $assignTitlePresentationPanel,
                ): void {
                    $presentation = $scheduleTitlePresentation->handle(
                        $request->user(),
                        $instance,
                        (int) $validated['room_id'],
                        Carbon::parse($validated['starts_at']),
                        Carbon::parse($validated['ends_at']),
                    );

                    $assignTitlePresentationPanel->handle($request->user(), $presentation, [
                        'chairperson' => $chairpersonId,
                        'member_1' => $panelUserIds[0],
                        'member_2' => $panelUserIds[1],
                    ]);
                }, 3);
            } else {
                $scheduleDefense->handle(
                    $request->user(),
                    $group,
                    $validated['defense_type'],
                    (int) $validated['room_id'],
                    Carbon::parse($validated['starts_at']),
                    Carbon::parse($validated['ends_at']),
                    $panelUserIds,
                    $chairpersonId,
                );
            }
        } catch (InvalidArgumentException $exception) {
            return to_route('facilitator.dashboard', ['tab' => 'defenses'])
                ->withErrors(['defense_schedule' => $exception->getMessage()])
                ->withInput();
        }

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
