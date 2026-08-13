<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Forms | NDMU-RMAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f7f6] text-gray-900">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[.22em] text-amber-500">NDMU Research Management</p>
                <h1 class="mt-1 text-2xl font-black text-[#0e5c3a]">Official Research Forms</h1>
            </div>
            <a href="{{ route('dashboard') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50">Back to Dashboard</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-8 px-6 py-8">
        @if (session('official_form_success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('official_form_success') }}</div>
        @endif
        @if ($errors->has('official_form'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('official_form') }}</div>
        @endif

        <section class="rounded-3xl bg-[#0e5c3a] p-7 text-white shadow-lg">
            <h2 class="text-xl font-black">Saved authoritative records</h2>
            <p class="mt-1 text-sm text-white/70">Every edit is stored as an immutable version. Access is restricted by permission and academic assignment.</p>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($instances as $instance)
                <a href="{{ route('official-forms.workspace.show', $instance) }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black text-[#0e5c3a]">{{ $instance->definition->code }}</p>
                            <h3 class="mt-1 font-bold">{{ $instance->definition->title }}</h3>
                        </div>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase text-amber-700">{{ str($instance->status)->headline() }}</span>
                    </div>
                    <p class="mt-4 text-xs text-gray-500">
                        {{ $instance->group?->name ?? $instance->researchClass?->name ?? 'Authorized context' }}
                        · Version {{ $instance->currentVersion?->version_number ?? 0 }}
                    </p>
                </a>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">No saved official form is accessible in your current academic assignments.</div>
            @endforelse
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-[#0e5c3a]">Create an authorized form record</h2>
            <p class="mt-1 text-xs text-gray-500">Only forms allowed by your permissions and exact group/class assignment will be created. Source-bound forms are opened from their authoritative consultation, review, revision, or validation request.</p>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($definitions as $definition)
                    @php
                        $code = strtolower($definition->code);
                        $hasFormPermission = auth()->user()->can('users.manage') || auth()->user()->getAllPermissions()->contains(fn ($permission) => str_starts_with($permission->name, "forms.{$code}."));
                        $sourceBound = in_array($definition->code, ['RES-031', 'RES-039', 'RES-043A', 'RES-043B'], true);
                        $blocked = in_array($definition->code, ['RES-029', 'RES-036', 'RES-037'], true);
                    @endphp
                    @if ($hasFormPermission)
                        <form method="POST" action="{{ route('official-forms.workspace.store', $definition) }}" class="rounded-2xl border border-gray-200 p-4">
                            @csrf
                            <p class="text-xs font-black text-[#0e5c3a]">{{ $definition->code }}</p>
                            <p class="mt-1 min-h-10 text-sm font-bold">{{ $definition->title }}</p>
                            @if ($definition->ownership_scope === 'research_group')
                                <select name="group_id" class="mt-3 w-full rounded-xl border border-gray-200 px-3 py-2 text-xs" @disabled(auth()->user()->user_type->value === 'student')>
                                    <option value="">{{ auth()->user()->user_type->value === 'student' ? 'Your current group is selected securely' : 'Select research group' }}</option>
                                    @foreach ($contexts['groups'] as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }} — {{ $group->researchClass?->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <select name="class_id" class="mt-3 w-full rounded-xl border border-gray-200 px-3 py-2 text-xs">
                                    <option value="">Select research class</option>
                                    @foreach ($contexts['classes'] as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @if ($definition->cardinality === 'single_per_context')
                                <select name="context_key" class="mt-2 w-full rounded-xl border border-gray-200 px-3 py-2 text-xs">
                                    <option value="proposal_defense">Proposal defense</option>
                                    <option value="final_defense">Final defense</option>
                                </select>
                            @endif
                            <button type="submit" @disabled($sourceBound || $blocked) class="mt-3 w-full rounded-xl bg-[#0e5c3a] px-3 py-2 text-xs font-bold text-white disabled:cursor-not-allowed disabled:bg-gray-300">
                                {{ $blocked ? 'Blocked pending verification' : ($sourceBound ? 'Open from source record' : 'Create / Open Form') }}
                            </button>
                        </form>
                    @endif
                @endforeach
            </div>
        </section>

        @if (auth()->user()->can('users.manage') || $contexts['classes']->contains('facilitator_id', auth()->id()))
            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-black text-[#0e5c3a]">Class institutional actors</h2>
                <p class="mt-1 text-xs text-gray-500">Manage exact Research Instructor, Program Coordinator, and Dean assignments.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($contexts['classes'] as $class)
                        @if (auth()->user()->can('users.manage') || (int) $class->facilitator_id === (int) auth()->id())
                            <a href="{{ route('official-form-class-actors.index', $class) }}" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-bold text-[#0e5c3a]">{{ $class->name }}</a>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</body>
</html>
