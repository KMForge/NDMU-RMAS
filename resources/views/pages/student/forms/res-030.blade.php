@php
    $officialFormInstance = $officialFormInstance ?? null;
    $payload = $payload ?? [];
    $group = $officialFormInstance?->group;
    $class = $officialFormInstance?->researchClass ?? $group?->researchClass;
    $resolver = app(\App\Modules\OfficialForms\Services\InstitutionalActorResolver::class);

    $currentResearchTitle = $payload['research_title']
        ?? $payload['title']
        ?? $group?->researchGroup?->currentProject?->title
        ?? $group?->titlePresentation?->approved_title
        ?? '';

    $members = $group?->members?->values() ?? collect();
    $joinedResearchers = $members->map(fn($m) => $m->student?->name)->filter()->join(', ');
    $program = $members->first()?->student?->studentProfile?->program;
    $programName = $payload['degree_program'] ?? $program?->name ?? $members->first()?->student?->program ?? 'Information Technology';

    $changeRequest = $officialFormInstance?->adviserChangeRequest;
    $requestedAdviserId = old('payload.requested_adviser_id', $payload['requested_adviser_id'] ?? $changeRequest?->requested_adviser_id);
    $isEditable = in_array($officialFormInstance?->status, ['draft', 'returned_for_correction'], true);
@endphp
<div x-show="activeOfficialForm === 'RES-030'" x-cloak>
    <x-student-official-form code="RES-Form-030" title="Adviser Change Request Form" guidebook-page="105">
        <input type="hidden" name="payload[personnel_type][]" value="Change of Research Adviser">
        <input type="hidden" name="payload[current_names][]" value="{{ $group?->adviser?->name }}">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="flex items-center gap-2">Date: <input type="date" name="payload[date]" value="{{ $payload['date'] ?? now()->format('Y-m-d') }}" class="w-full"></label>
            <label class="flex items-center gap-2">Degree Program: <input name="payload[degree_program]" value="{{ $programName }}" class="w-full font-semibold" readonly></label>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm">
            <p><strong>Research group:</strong> {{ $group?->name }}</p>
            <p><strong>Class:</strong> {{ $class?->name }}</p>
            <p><strong>Group leader:</strong> {{ $group?->leader?->name }}</p>
            <p><strong>Members:</strong> {{ $joinedResearchers }}</p>
            <p><strong>Current adviser:</strong> {{ $group?->adviser?->name ?? 'No active adviser' }}</p>
        </div>
        <label class="block font-bold">Research Title:<input name="payload[research_title]" value="{{ $currentResearchTitle }}" class="w-full font-bold" readonly></label>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="block font-bold">Current Adviser
                <input value="{{ $group?->adviser?->name }}" class="mt-1 w-full" readonly>
            </label>
            <label class="block font-bold">Requested New Adviser
                <select name="payload[requested_adviser_id]" class="mt-1 w-full" required @disabled(! $isEditable)>
                    <option value="">Select an active eligible adviser</option>
                    @foreach (($adviserCandidates ?? collect()) as $candidate)
                        <option value="{{ $candidate->id }}" @selected((int) $requestedAdviserId === (int) $candidate->id)>{{ $candidate->name }} — {{ $candidate->email }}</option>
                    @endforeach
                    @if ($changeRequest?->requestedAdviser && ! ($adviserCandidates ?? collect())->contains('id', $changeRequest->requested_adviser_id))
                        <option value="{{ $changeRequest->requested_adviser_id }}" selected>{{ $changeRequest->requestedAdviser->name }} — {{ $changeRequest->requestedAdviser->email }}</option>
                    @endif
                </select>
                @unless($isEditable)<input type="hidden" name="payload[requested_adviser_id]" value="{{ $requestedAdviserId }}">@endunless
            </label>
        </div>
        <label class="block font-bold">Reason for adviser change<textarea name="payload[reasons]" class="mt-2 min-h-24 w-full" required placeholder="State the reason for requesting a new adviser...">{{ $payload['reasons'] ?? $changeRequest?->reason }}</textarea></label>
        <label class="block font-bold">Supporting explanation <span class="font-normal text-slate-500">(when required)</span><textarea name="payload[supporting_explanation]" class="mt-2 min-h-20 w-full" placeholder="Add relevant context or explain the supporting evidence...">{{ $payload['supporting_explanation'] ?? $changeRequest?->supporting_explanation }}</textarea></label>
        @if ($isEditable)
            <label class="block font-bold">Supporting document <span class="font-normal text-slate-500">(optional unless required by the reviewer)</span>
                <input type="file" name="supporting_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="mt-2 block w-full rounded-lg border border-slate-300 p-2 text-sm">
                <span class="mt-1 block text-xs font-normal text-slate-500">PDF, Word, JPG, or PNG; maximum 10 MB. Stored privately.</span>
            </label>
        @elseif ($changeRequest?->supporting_document_path)
            <a href="{{ route('official-forms.workspace.adviser-change-supporting-document', $officialFormInstance) }}" class="inline-flex rounded-lg border border-emerald-300 px-4 py-2 text-sm font-bold text-emerald-800">Download supporting document</a>
        @endif
        <label class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold">
            <input type="hidden" name="payload[group_leader_confirmed]" value="0">
            <input type="checkbox" name="payload[group_leader_confirmed]" value="1" class="mt-1" required @checked((bool) ($payload['group_leader_confirmed'] ?? $changeRequest?->leader_confirmed_at)) @disabled(! $isEditable)>
            <span>I, {{ $group?->leader?->name }}, confirm that I am the research group leader and authorize submission of this adviser change request.</span>
        </label>
        @if ($changeRequest)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                <p><strong>Request status:</strong> {{ str($changeRequest->status)->headline() }}</p>
                @if ($changeRequest->reviewer)
                    <p><strong>Authorized reviewer:</strong> {{ $changeRequest->reviewer->name }}</p>
                    <p><strong>Reviewer remarks:</strong> {{ $changeRequest->reviewer_remarks ?: 'None' }}</p>
                    <p><strong>Decision date:</strong> {{ $changeRequest->reviewed_at?->format('M j, Y g:i A') }}</p>
                    @if ($changeRequest->effective_at)<p><strong>Effective date:</strong> {{ $changeRequest->effective_at->format('M j, Y g:i A') }}</p>@endif
                @endif
            </div>
        @endif
    </x-student-official-form>
</div>
