<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Form Actors | NDMU-RMAS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f7f6] text-gray-900">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col items-stretch gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-500">Official Forms Context</p>
                <h1 class="text-2xl font-black text-[#0e5c3a]">{{ $researchClass->name }}</h1>
                <p class="text-xs text-gray-500">Institutional actor assignments</p>
            </div>
            <a href="{{ route('official-forms.workspace.index') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-center text-xs font-bold sm:shrink-0">Back to Forms</a>
        </div>
    </header>

    <main class="mx-auto max-w-6xl space-y-5 px-4 py-6 sm:px-6 sm:py-8">
        @if (session('class_actor_success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('class_actor_success') }}</div>
        @endif
        @if ($errors->has('class_actor'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('class_actor') }}</div>
        @endif

        <section class="rounded-3xl bg-[#0e5c3a] p-7 text-white">
            <h2 class="text-xl font-black">Class-scoped academic authority</h2>
            <p class="mt-1 text-sm text-white/70">Assignments control who may perform verified academic actions for this class. They do not edit Spatie roles or grant missing permissions.</p>
        </section>

        <section class="grid gap-5 md:grid-cols-3">
            @foreach (['research_instructor' => 'Research Instructor', 'program_coordinator' => 'Program Coordinator', 'dean' => 'College Dean'] as $actorType => $label)
                @php($assignment = $researchClass->officialFormActorAssignments->firstWhere('actor_type', $actorType))
                <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-wider text-[#0e5c3a]">{{ $label }}</p>
                    <p class="mt-3 font-bold">{{ $assignment?->user?->name ?? 'No active assignment' }}</p>
                    <p class="mt-1 truncate text-xs text-gray-500">{{ $assignment?->user?->email }}</p>
                    @if ($assignment)
                        <p class="mt-3 text-[10px] text-gray-500">Assigned {{ $assignment->assigned_at?->format('M j, Y g:i A') }} by {{ $assignment->assigner?->name }}</p>
                    @endif

                    <form method="POST" action="{{ route('official-form-class-actors.store', $researchClass) }}" class="mt-4 space-y-2">
                        @csrf
                        <input type="hidden" name="actor_type" value="{{ $actorType }}">
                        <select name="user_id" required class="w-full rounded-xl border border-gray-200 px-3 py-2 text-xs">
                            <option value="">{{ $assignment ? 'Choose replacement' : 'Choose eligible faculty' }}</option>
                            @foreach ($candidates->get($actorType, collect()) as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->name }} — {{ $candidate->email }}</option>
                            @endforeach
                        </select>
                        <button class="w-full rounded-xl bg-[#0e5c3a] px-3 py-2 text-xs font-bold text-white">{{ $assignment ? 'Replace' : 'Assign' }}</button>
                    </form>

                    @if ($assignment)
                        <form method="POST" action="{{ route('official-form-class-actors.destroy', [$researchClass, $assignment]) }}" class="mt-2" onsubmit="return confirm('Deactivate this assignment?')">
                            @csrf
                            @method('DELETE')
                            <button class="w-full rounded-xl border border-red-200 px-3 py-2 text-xs font-bold text-red-600">Deactivate</button>
                        </form>
                    @endif
                </article>
            @endforeach
        </section>
    </main>
</body>
</html>
