<?php

namespace App\Http\Controllers\Facilitator;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\ResearchClass;
use App\Models\ResearchClassActorAssignment;
use App\Models\User;
use App\Modules\OfficialForms\Actions\AssignResearchClassFormActor;
use App\Modules\OfficialForms\Actions\DeactivateResearchClassFormActor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ResearchClassFormActorController extends Controller
{
    public function index(Request $request, ResearchClass $researchClass): View
    {
        abort_unless($request->user()->can('users.manage') || (int) $researchClass->facilitator_id === (int) $request->user()->id, 403);

        $researchClass->load([
            'facilitator:id,name,email',
            'officialFormActorAssignments' => fn ($query) => $query
                ->where('status', 'active')
                ->with(['user:id,name,email', 'assigner:id,name,email'])
                ->orderBy('actor_type'),
        ]);

        $candidates = collect([
            'research_instructor' => ['forms.res-041.fill', 'forms.res-041.endorse'],
            'program_coordinator' => ['forms.res-041.receive'],
            'dean' => ['forms.res-047.approve'],
        ])->map(fn (array $permissions) => User::query()
            ->where('user_type', 'faculty')
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->permission($permissions)
            ->orderBy('name')
            ->get(['id', 'name', 'email']));

        return view('pages.official-forms.class-actors', compact('researchClass', 'candidates'));
    }

    public function store(
        Request $request,
        ResearchClass $researchClass,
        AssignResearchClassFormActor $assignActor,
    ): RedirectResponse {
        $validated = $request->validate([
            'actor_type' => ['required', Rule::in(['research_instructor', 'program_coordinator', 'dean'])],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $assignActor->handle(
                $request->user(),
                $researchClass,
                User::query()->findOrFail($validated['user_id']),
                $validated['actor_type'],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['class_actor' => $exception->getMessage()]);
        }

        return back()->with('class_actor_success', 'Institutional actor assignment saved.');
    }

    public function destroy(
        Request $request,
        ResearchClass $researchClass,
        ResearchClassActorAssignment $assignment,
        DeactivateResearchClassFormActor $deactivateActor,
    ): RedirectResponse {
        try {
            $deactivateActor->handle($request->user(), $researchClass, $assignment);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['class_actor' => $exception->getMessage()]);
        }

        return back()->with('class_actor_success', 'Institutional actor assignment deactivated.');
    }
}
