<?php

namespace App\Http\Controllers\Facilitator;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Modules\Documents\Actions\ScreenTitleProposalDocument;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TitleProposalScreeningController extends Controller
{
    public function __invoke(Request $request, Document $document, ScreenTitleProposalDocument $screen): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:revision_required,approved_for_presentation'],
            'remarks' => ['nullable', 'string', 'max:2000', 'required_if:decision,revision_required'],
        ]);

        try {
            $screen->handle($request->user(), $document, $validated['decision'], $validated['remarks'] ?? null, $request->ip());
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['title_proposal' => $exception->getMessage()]);
        }

        return back()->with('status', 'Title Proposal screening decision recorded.');
    }
}
