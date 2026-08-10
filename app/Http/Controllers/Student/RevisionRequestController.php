<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\RevisionRequest;
use App\Modules\Revisions\Actions\StartRevisionCycle;
use App\Modules\Revisions\Actions\SubmitRevisionDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

class RevisionRequestController extends Controller
{
    public function start(Request $request, RevisionRequest $revisionRequest, StartRevisionCycle $startAction): RedirectResponse
    {
        $this->authorize('start', $revisionRequest);

        $startAction->handle($request->user(), $revisionRequest);

        return back()->with('status', 'Revision work officially started.');
    }

    public function submit(Request $request, RevisionRequest $revisionRequest, SubmitRevisionDocument $submitAction): RedirectResponse
    {
        $this->authorize('submit', $revisionRequest);

        $validated = $request->validate([
            'submission_token' => ['required', 'string', 'uuid'],
            'document' => [
                'required',
                'file',
                File::types(['pdf', 'docx'])->max(10 * 1024),
            ],
        ]);

        $submitAction->handle(
            $request->user(),
            $request->file('document'),
            $validated['submission_token'],
            $request->ip() ?? '127.0.0.1',
            $revisionRequest,
        );

        return back()->with('status', 'Corrected research document submitted successfully.');
    }
}
