<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $instance->definition->code }} | NDMU-RMAS</title>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="official-form-workspace min-h-screen bg-[#eef2f0] text-slate-900">
    <header class="border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-500">Authoritative Official Form</p>
                <h1 class="text-xl font-black text-[#164b38]">{{ $instance->definition->code }} · {{ $instance->definition->title }}</h1>
            </div>
            <a href="{{ route('official-forms.workspace.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm hover:border-[#2b7659] hover:text-[#164b38]">Back to Forms</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-5 px-6 py-6" x-data="{ activeOfficialForm: @js($instance->definition->code), activeTab: 'forms' }">
        @if (session('official_form_success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('official_form_success') }}</div>
        @endif
        @if (isset($errors) && $errors->any())
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
                    <div class="mt-4 space-y-4 rounded-2xl bg-slate-50 p-4 border border-slate-200">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800">Option A: Approve One Title</span>
                            <form method="POST" action="{{ route('facilitator.title-presentations.result', $titlePresentation) }}" class="mt-2 grid gap-3 md:grid-cols-[12rem_1fr_auto]">
                                @csrf @method('PATCH')
                                <select name="approved_title_number" required class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-slate-800">
                                    <option value="">Approved Title No.</option>
                                    <option value="1">Title #1</option>
                                    <option value="2">Title #2</option>
                                    <option value="3">Title #3</option>
                                </select>
                                <input name="remarks" maxlength="2000" placeholder="Panel commendations / remarks (optional)" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium">
                                <button type="submit" class="rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-5 py-2 text-xs font-black text-white transition-colors cursor-pointer flex items-center gap-1.5">
                                    <i class="ph ph-check-circle"></i> Approve Selected Title
                                </button>
                            </form>
                        </div>

                        <hr class="border-slate-200">

                        <div>
                            <span class="text-[10px] font-black uppercase tracking-wider text-rose-700">Option B: Disapprove All Proposed Titles (Require Re-Proposal)</span>
                            <form method="POST" action="{{ route('facilitator.title-presentations.disapprove', $titlePresentation) }}" onsubmit="return confirm('Disapprove all 3 proposed titles? The research group will be required to submit brand-new title topics and an updated document.')" class="mt-2 grid gap-3 md:grid-cols-[1fr_auto]">
                                @csrf @method('PATCH')
                                <input name="remarks" required minlength="3" maxlength="2000" placeholder="Reason why all 3 titles are disapproved (required for student re-proposal guidance)..." class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-medium text-slate-900 focus:border-rose-500 focus:outline-none">
                                <button type="submit" class="rounded-xl border border-rose-300 bg-rose-600 hover:bg-rose-700 px-5 py-2 text-xs font-black text-white transition-colors cursor-pointer flex items-center gap-1.5">
                                    <i class="ph ph-x-circle"></i> Disapprove All &amp; Require Re-Proposal
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @if (!in_array(strtoupper($instance->definition->code), ['RES-036', 'RES-037'], true))
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[.18em] text-emerald-600">Institutional routing</p>
                        <h2 class="mt-1 font-black text-[#0e5c3a]">Academic Signers & Officers</h2>
                        <p class="mt-1 text-xs text-gray-500">Signers and endorsements are automatically resolved from class, department, and college assignments.</p>
                    </div>
                </div>

                <!-- Auto-Resolved Departmental Actors Display -->
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @if (isset($autoResolvedActors['program_coordinator']))
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3.5 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Program Coordinator</span>
                            <p class="text-xs font-black text-gray-900 truncate">{{ $autoResolvedActors['program_coordinator']->name }}</p>
                            <p class="text-[10px] text-emerald-700">Auto-resolved from department</p>
                        </div>
                    @endif
                    @if (isset($autoResolvedActors['facilitator']))
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3.5 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Research Instructor</span>
                            <p class="text-xs font-black text-gray-900 truncate">{{ $autoResolvedActors['facilitator']->name }}</p>
                            <p class="text-[10px] text-emerald-700">Class Instructor</p>
                        </div>
                    @endif
                    @if (isset($autoResolvedActors['adviser']))
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3.5 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Thesis Adviser</span>
                            <p class="text-xs font-black text-gray-900 truncate">{{ $autoResolvedActors['adviser']->name }}</p>
                            <p class="text-[10px] text-emerald-700">Assigned Adviser</p>
                        </div>
                    @endif
                    @if (isset($autoResolvedActors['dean']))
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3.5 space-y-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">College Dean</span>
                            <p class="text-xs font-black text-gray-900 truncate">{{ $autoResolvedActors['dean']->name }}</p>
                            <p class="text-[10px] text-emerald-700">CEAC Dean</p>
                        </div>
                    @endif
                </div>

                @if ($canManageActors && $actorOptions->isNotEmpty())
                    <details class="mt-4 border-t border-gray-100 pt-3">
                        <summary class="text-xs font-bold text-gray-500 hover:text-gray-700 cursor-pointer">
                            Need a custom actor override? Click to assign explicit instance actors
                        </summary>
                        <div class="mt-3 grid gap-4 lg:grid-cols-2">
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
                                        <button type="submit" class="rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-bold text-white">Override</button>
                                    </div>
                                    @if ($candidates->isEmpty())
                                        <p class="mt-2 text-[10px] font-semibold text-amber-700">No active, approved faculty currently has the required permission.</p>
                                    @endif
                                </form>
                            @endforeach
                        </div>

                        <div class="mt-3 space-y-2">
                            @foreach ($instance->actorAssignments->where('status', 'active') as $assignment)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-100 px-4 py-3 text-xs">
                                    <div>
                                        <span class="font-bold">{{ $assignment->user?->name }}</span>
                                        <span class="text-gray-500">· {{ str($assignment->actor_type)->headline() }} · assigned {{ $assignment->assigned_at?->format('M j, Y g:i A') }}</span>
                                    </div>
                                    <form method="POST" action="{{ route('official-forms.workspace.actors.destroy', [$instance, $assignment]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 font-bold text-red-600">Deactivate</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
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

        <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-slate-900 shadow-sm">
            @can('updateDraft', $instance)
                <button type="submit" form="official-form-editor" class="rounded-xl bg-[#1e684c] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#17533d] transition-colors">Save New Draft Version</button>
                <button type="submit" form="official-form-editor" formaction="{{ route('official-forms.workspace.submit', $instance) }}" formmethod="POST" class="rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-bold text-white hover:bg-blue-700 transition-colors shadow-sm">
                    {{ strtoupper($instance->definition->code) === 'RES-036' ? 'Sign & Submit Evaluation' : 'Submit' }}
                </button>
            @endcan
            @if ($instance->status === 'submitted')
                <div class="inline-flex items-center gap-2 rounded-xl bg-emerald-100 px-4 py-2 text-xs font-black text-emerald-800">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span> Evaluation Submitted
                </div>
            @endif
            @foreach ($availableActions as $action)
                <form method="POST" action="{{ route('official-forms.workspace.sign-action', [$instance, $action]) }}">
                    @csrf
                    <input type="hidden" name="expected_version_id" value="{{ $instance->current_version_id }}">
                    <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-[#0e5c3a]">
                        {{ match ($action) {
                            'sign' => strtoupper($instance->definition->code) === 'RES-037' ? 'Sign Evaluation Summary (RES-037)' : 'Sign Form',
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
                    <button type="submit" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white hover:bg-emerald-600">Sign Authorship Attestation</button>
                </form>
            @endif
            <a href="{{ route('official-forms.print', $instance) }}" target="_blank" rel="noopener" class="ml-auto rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-700 shadow-sm hover:border-[#2b7659] hover:text-[#164b38]">Print saved version</a>
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
    @livewireScripts
</body>
</html>
