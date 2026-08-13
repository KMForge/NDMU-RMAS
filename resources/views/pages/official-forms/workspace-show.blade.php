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
                <form method="POST" action="{{ route('official-forms.workspace.action', [$instance, $action]) }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-[#0e5c3a]">{{ str($action)->headline() }}</button>
                </form>
            @endforeach
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
