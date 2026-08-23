<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $instance->definition->code }} | NDMU-RMAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f7f6] text-gray-900">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-500">Authoritative Official Form</p>
                <h1 class="text-xl font-black text-[#0e5c3a]">{{ $instance->definition->code }} · {{ $instance->definition->title }}</h1>
            </div>
            <a href="{{ route('official-forms.workspace.index') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold">Back to Forms</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-5 px-6 py-6" x-data="{ activeOfficialForm: @js($instance->definition->code), activeTab: 'forms' }">
        @if (session('official_form_success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('official_form_success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <section class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:grid-cols-4">
            <div><p class="text-[9px] font-black uppercase text-gray-400">Status</p><p class="mt-1 text-sm font-bold">{{ str($instance->status)->headline() }}</p></div>
            <div><p class="text-[9px] font-black uppercase text-gray-400">Context</p><p class="mt-1 text-sm font-bold">{{ $instance->group?->name ?? $instance->researchClass?->name }}</p></div>
            <div><p class="text-[9px] font-black uppercase text-gray-400">Current version</p><p class="mt-1 text-sm font-bold">V{{ $instance->currentVersion?->version_number ?? 0 }}</p></div>
            <div><p class="text-[9px] font-black uppercase text-gray-400">Actors</p><p class="mt-1 text-xs font-semibold">{{ $instance->actorAssignments->where('status', 'active')->map(fn ($a) => $a->user?->name.' ('.str($a->actor_type)->headline().')')->filter()->join(', ') ?: 'Derived from academic context' }}</p></div>
        </section>

        @if (strtoupper($instance->definition->code) === 'RES-026' && $isOwningFacilitator)
            @php
                $titlePresentation = $instance->titlePresentation;
                $titlePresentationNextStep = match ($titlePresentation?->status) {
                    null => 'Schedule the Title Presentation and select its venue and time slot.',
                    'scheduled' => 'Assign one Chairperson and two Panel Members.',
                    'panel_assigned' => 'After the presentation has been conducted, mark it completed. The Approved Research Title No. remains locked until then.',
                    'presented' => 'Select the approved title number from the three titles in the submitted RES-026.',
                    'awaiting_panel_signatures' => 'The assigned Chairperson and two Panel Members must apply their signatures in their exact panel positions.',
                    'awaiting_program_coordinator' => 'The Program Coordinator assigned to this Capstone class must sign and endorse RES-026.',
                    'awaiting_dean' => 'The College Dean assigned to this Capstone class must provide final approval.',
                    'finalized' => 'RES-026 is finalized and the approved title is now the canonical research title.',
                    default => 'Continue the verified RES-026 workflow.',
                };
            @endphp
            <section class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[.18em] text-amber-500">Acting as Research Facilitator</p>
                <h2 class="mt-1 text-lg font-black text-[#0e5c3a]">Title Presentation Workflow</h2>
                <p class="mt-1 text-xs text-gray-500">Group: {{ $instance->group?->name }} · State: {{ str($titlePresentation?->status ?? 'awaiting_schedule')->headline() }}</p>
                <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                    <span class="font-black">Next required action:</span> {{ $titlePresentationNextStep }}
                </div>

                @if ($instance->status === 'submitted' && $titlePresentation === null)
                    <form method="POST" action="{{ route('facilitator.title-presentations.store', $instance) }}" class="mt-4 grid gap-3 md:grid-cols-4">
                        @csrf
                        <select name="room_id" required class="rounded-xl border border-gray-200 px-3 py-2 text-xs">
                            <option value="">Select venue</option>
                            @foreach ($defenseRooms as $room)<option value="{{ $room->id }}">{{ $room->code }} · {{ $room->name }}</option>@endforeach
                        </select>
                        <input type="datetime-local" name="starts_at" required class="rounded-xl border border-gray-200 px-3 py-2 text-xs">
                        <input type="datetime-local" name="ends_at" required class="rounded-xl border border-gray-200 px-3 py-2 text-xs">
                        <button class="rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-bold text-white">Schedule Title Presentation</button>
                    </form>
                @elseif ($titlePresentation?->status === 'scheduled')
                    <form method="POST" action="{{ route('facilitator.title-presentations.panel', $titlePresentation) }}" class="mt-4 grid gap-3 md:grid-cols-4">
                        @csrf @method('PUT')
                        @foreach (['chairperson_user_id' => 'Chairperson', 'member_1_user_id' => 'Panel Member 1', 'member_2_user_id' => 'Panel Member 2'] as $field => $label)
                            <select name="{{ $field }}" required class="rounded-xl border border-gray-200 px-3 py-2 text-xs"><option value="">{{ $label }}</option>@foreach ($titlePanelCandidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->name }}</option>@endforeach</select>
                        @endforeach
                        <button class="rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-bold text-white">Assign Chair & Panel</button>
                    </form>
                @elseif ($titlePresentation?->status === 'panel_assigned')
                    <form method="POST" action="{{ route('facilitator.title-presentations.complete', $titlePresentation) }}" class="mt-4">@csrf @method('PATCH')<button class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white">Mark Presentation Completed</button></form>
                @elseif ($titlePresentation?->status === 'presented')
                    <form method="POST" action="{{ route('facilitator.title-presentations.result', $titlePresentation) }}" class="mt-4 grid gap-3 md:grid-cols-[12rem_1fr_auto]">
                        @csrf @method('PATCH')
                        <select name="approved_title_number" required class="rounded-xl border border-gray-200 px-3 py-2 text-xs"><option value="">Approved Title No.</option><option value="1">1</option><option value="2">2</option><option value="3">3</option></select>
                        <input name="remarks" maxlength="2000" placeholder="Official remarks (optional)" class="rounded-xl border border-gray-200 px-3 py-2 text-xs">
                        <button class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white">Record Result</button>
                    </form>
                @endif
            </section>
        @endif

        @if ($canManageActors && $actorOptions->isNotEmpty())
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[.18em] text-amber-500">Context assignment</p>
                        <h2 class="mt-1 font-black text-[#0e5c3a]">Academic actors for this form</h2>
                        <p class="mt-1 text-xs text-gray-500">This assigns a person to this exact form instance. It does not change their Spatie roles.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    @foreach ($actorOptions as $actorType => $candidates)
                        <form method="POST" action="{{ route('official-forms.workspace.actors.store', $instance) }}" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            @csrf
                            <input type="hidden" name="actor_type" value="{{ $actorType }}">
                            <label class="text-xs font-bold text-gray-700">{{ str($actorType)->headline() }}</label>
                            <div class="mt-2 flex gap-2">
                                <select name="user_id" required class="min-w-0 flex-1 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs">
                                    <option value="">Select eligible faculty</option>
                                    @foreach ($candidates as $candidate)
                                        <option value="{{ $candidate->id }}">{{ $candidate->name }} ({{ $candidate->email }})</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-bold text-white">Assign</button>
                            </div>
                            @if ($candidates->isEmpty())
                                <p class="mt-2 text-[10px] font-semibold text-amber-700">No active, approved faculty currently has the required permission.</p>
                            @endif
                        </form>
                    @endforeach
                </div>

                <div class="mt-4 space-y-2">
                    @forelse ($instance->actorAssignments->where('status', 'active') as $assignment)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 px-4 py-3 text-xs">
                            <div>
                                <span class="font-bold">{{ $assignment->user?->name }}</span>
                                <span class="text-gray-500">Â· {{ str($assignment->actor_type)->headline() }} Â· assigned {{ $assignment->assigned_at?->format('M j, Y g:i A') }}</span>
                            </div>
                            <form method="POST" action="{{ route('official-forms.workspace.actors.destroy', [$instance, $assignment]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 font-bold text-red-600">Deactivate</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500">No active instance-scoped actor is assigned.</p>
                    @endforelse
                </div>
            </section>
        @endif

        <form id="official-form-editor" method="POST" action="{{ route('official-forms.workspace.save', $instance) }}">
            @csrf
            @if (view()->exists($instance->definition->template_view))
                @include($instance->definition->template_view, [
                    'officialFormInstance' => $instance,
                    'payload' => old('payload', $payload),
                ])
            @else
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
                    <h3 class="text-base font-bold">Template Under Verification</h3>
                    <p class="mt-1 text-xs text-amber-800">The dedicated view template for {{ $instance->definition->code }} ({{ $instance->definition->title }}) is not yet available in the catalog view path [{{ $instance->definition->template_view }}]. Form instance data and status are preserved in the database backend.</p>
                </div>
            @endif
        </form>

        <section class="sticky bottom-4 z-20 flex flex-wrap items-center gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-xl backdrop-blur">
            @can('updateDraft', $instance)
                <button type="submit" form="official-form-editor" class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white">Save New Draft Version</button>
                <button type="submit" form="official-form-editor" formaction="{{ route('official-forms.workspace.submit', $instance) }}" formmethod="POST" class="rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white">Submit</button>
            @endcan
            @foreach ($availableActions as $action)
                <form method="POST" action="{{ route('official-forms.workspace.sign-action', [$instance, $action]) }}">
                    @csrf
                    <input type="hidden" name="expected_version_id" value="{{ $instance->current_version_id }}">
                    <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-[#0e5c3a]">
                        {{ match ($action) {
                            'sign_chairperson' => 'Sign as Chairperson',
                            'sign_member_1' => 'Sign as Panel Member 1',
                            'sign_member_2' => 'Sign as Panel Member 2',
                            default => 'Sign & '.str($action)->headline(),
                        } }}
                    </button>
                </form>
            @endforeach
            @if (strtoupper($instance->definition->code) === 'RES-049' && auth()->user()->hasPermissionTo('forms.res-049.sign'))
                <form method="POST" action="{{ route('official-forms.workspace.sign-action', [$instance, 'sign_authorship']) }}">
                    @csrf
                    <input type="hidden" name="expected_version_id" value="{{ $instance->current_version_id }}">
                    <input type="hidden" name="actor_type" value="student_researcher">
                    <button type="submit" class="rounded-xl bg-emerald-800 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-900">Sign Authorship Attestation</button>
                </form>
            @endif
            <a href="{{ route('official-forms.print', $instance) }}" target="_blank" rel="noopener" class="ml-auto rounded-xl border border-gray-200 px-5 py-2.5 text-xs font-bold">Print saved version</a>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="font-black text-[#0e5c3a]">Immutable version history</h2>
            <div class="mt-3 space-y-2">
                @foreach ($instance->versions->sortByDesc('version_number') as $version)
                    <div class="flex justify-between rounded-xl bg-gray-50 px-4 py-3 text-xs">
                        <span class="font-bold">Version {{ $version->version_number }} {{ $version->is_current ? '· Current' : '' }}</span>
                        <span class="text-gray-500">{{ $version->creator?->name }} · {{ $version->created_at?->format('M j, Y g:i A') }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</body>
</html>
