<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Revisions\StoreRevisionDocumentRequest;
use App\Models\RevisionRequest;
use App\Modules\Revisions\Actions\StartRevisionCycle;
use App\Modules\Revisions\Actions\SubmitRevisionDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RevisionRequestController extends Controller
{
    public function start(Request $request, RevisionRequest $revisionRequest, StartRevisionCycle $startAction): RedirectResponse
    {
        $this->authorize('start', $revisionRequest);

        $startAction->handle($request->user(), $revisionRequest);

        return back()->with('status', 'Revision work officially started.');
    }

    public function submit(StoreRevisionDocumentRequest $request, RevisionRequest $revisionRequest, SubmitRevisionDocument $submitAction): RedirectResponse
    {
        $submitAction->handle(
            $request->user(),
            $request->file('document'),
            (string) $request->input('submission_token'),
            $request->ip() ?? '127.0.0.1',
            $revisionRequest,
        );

        return back()->with('status', 'Corrected research document submitted successfully.');
    }
}
