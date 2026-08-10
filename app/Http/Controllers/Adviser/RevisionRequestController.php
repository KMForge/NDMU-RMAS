<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\RevisionRequest;
use App\Modules\Revisions\Actions\ReopenRevisionCycle;
use App\Modules\Revisions\Actions\ResolveRevisionCycle;
use App\Modules\Revisions\Actions\UpdateRevisionDueDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RevisionRequestController extends Controller
{
    public function resolve(Request $request, RevisionRequest $revisionRequest, ResolveRevisionCycle $resolveAction): RedirectResponse
    {
        $this->authorize('resolve', $revisionRequest);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $resolveAction->handle($request->user(), $revisionRequest, $validated['notes'] ?? null);

        return back()->with('status', 'Revision cycle resolved successfully.');
    }

    public function updateDueDate(Request $request, RevisionRequest $revisionRequest, UpdateRevisionDueDate $updateDueDateAction): RedirectResponse
    {
        $this->authorize('updateDueDate', $revisionRequest);

        $validated = $request->validate([
            'due_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $updateDueDateAction->handle($request->user(), $revisionRequest, $validated['due_at'] ?? null);

        return back()->with('status', 'Revision due date updated successfully.');
    }

    public function reopen(Request $request, RevisionRequest $revisionRequest, ReopenRevisionCycle $reopenAction): RedirectResponse
    {
        $this->authorize('reopen', $revisionRequest);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $reopenAction->handle($request->user(), $revisionRequest, $validated['reason']);

        return back()->with('status', 'Controlled reopening completed successfully.');
    }
}
