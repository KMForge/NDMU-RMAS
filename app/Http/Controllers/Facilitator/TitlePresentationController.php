<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Models\OfficialFormInstance;
use App\Models\TitlePresentation;
use App\Modules\DefenseScheduling\Services\DefenseEndorsementEligibility;
use App\Modules\TitlePresentations\Actions\AssignTitlePresentationPanel;
use App\Modules\TitlePresentations\Actions\CompleteTitlePresentation;
use App\Modules\TitlePresentations\Actions\RecordApprovedTitle;
use App\Modules\TitlePresentations\Actions\RecordDisapprovedTitlePresentation;
use App\Modules\TitlePresentations\Actions\ScheduleTitlePresentation;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TitlePresentationController extends Controller
{
    public function store(
        Request $request,
        OfficialFormInstance $instance,
        ScheduleTitlePresentation $schedule,
        DefenseEndorsementEligibility $endorsementEligibility,
    ): RedirectResponse {
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:defense_rooms,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        return $this->mutate(function () use ($request, $instance, $schedule, $endorsementEligibility, $validated) {
            $instance->loadMissing('group');
            if ($instance->group === null) {
                throw new InvalidArgumentException('The RES-026 form is not linked to a research group.');
            }

            $endorsementEligibility->ensureComplete($instance->group, 'title_presentation');

            return $schedule->handle(
                $request->user(),
                $instance,
                (int) $validated['room_id'],
                Carbon::parse($validated['starts_at']),
                Carbon::parse($validated['ends_at']),
            );
        }, 'Title Presentation scheduled.');
    }

    public function assignPanel(Request $request, TitlePresentation $presentation, AssignTitlePresentationPanel $assign): RedirectResponse
    {
        $validated = $request->validate([
            'chairperson_user_id' => ['required', 'integer', 'exists:users,id'],
            'member_1_user_id' => ['required', 'integer', 'different:chairperson_user_id', 'exists:users,id'],
            'member_2_user_id' => ['required', 'integer', 'different:chairperson_user_id', 'different:member_1_user_id', 'exists:users,id'],
            'change_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->mutate(fn () => $assign->handle($request->user(), $presentation, [
            'chairperson' => (int) $validated['chairperson_user_id'],
            'member_1' => (int) $validated['member_1_user_id'],
            'member_2' => (int) $validated['member_2_user_id'],
        ], $validated['change_reason'] ?? null), 'Title Presentation panel saved.');
    }

    public function complete(
        Request $request,
        TitlePresentation $presentation,
        CompleteTitlePresentation $complete,
        DefenseEndorsementEligibility $endorsementEligibility,
    ): RedirectResponse {
        return $this->mutate(function () use ($request, $presentation, $complete, $endorsementEligibility) {
            $presentation->loadMissing('defense.group');
            if ($presentation->defense?->group === null) {
                throw new InvalidArgumentException('The Title Presentation is not linked to a research group.');
            }

            $endorsementEligibility->ensureComplete($presentation->defense->group, 'title_presentation');

            return $complete->handle($request->user(), $presentation);
        }, 'Title Presentation marked completed.');
    }

    public function recordResult(Request $request, TitlePresentation $presentation, RecordApprovedTitle $record): RedirectResponse
    {
        $validated = $request->validate([
            'approved_title_number' => ['required', 'integer', 'in:1,2,3'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->mutate(fn () => $record->handle($request->user(), $presentation, (int) $validated['approved_title_number'], $validated['remarks'] ?? null), 'Approved Research Title No. recorded.');
    }

    public function disapprove(Request $request, TitlePresentation $presentation, RecordDisapprovedTitlePresentation $disapprove): RedirectResponse
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        return $this->mutate(fn () => $disapprove->handle($request->user(), $presentation, $validated['remarks']), 'Title Presentation marked as Disapproved. Re-proposal cycle initiated.');
    }

    private function mutate(callable $callback, string $message): RedirectResponse
    {
        try {
            $callback();
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['title_presentation' => $exception->getMessage()]);
        }

        return back()->with('status', $message);
    }
}
