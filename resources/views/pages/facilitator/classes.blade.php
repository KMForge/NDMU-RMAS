<section class="space-y-8" x-data="{ showCreateClassModal: @js($errors->hasAny(['class', 'creation_token', 'name', 'description', 'max_students'])) }">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold font-heading text-gray-800">My Classes</h1>
            <p class="mt-1 text-xs text-gray-500">Create Capstone classes and view approved student rosters.</p>
        </div>
        <button type="button" @click="showCreateClassModal = true" class="flex items-center gap-2 rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white shadow-md hover:bg-[#0a4a2e]">
            <i class="ph ph-plus-circle text-base"></i>
            <span>Create Class</span>
        </button>
    </div>

    @if (session('class_success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('class_success') }}
        </div>
    @endif

    @if ($errors->has('class'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('class') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($researchClasses as $researchClass)
            <a href="{{ route('facilitator.classes.show', $researchClass) }}" class="block rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all hover:border-[#0e5c3a]/30 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-[10px] font-extrabold tracking-wider text-amber-700">
                        {{ $researchClass->revealJoinCode() }}
                    </span>
                    <i class="ph ph-caret-right text-lg text-gray-400"></i>
                </div>
                <div class="mt-4">
                    <h2 class="text-sm font-bold text-gray-800">{{ $researchClass->name }}</h2>
                    @if ($researchClass->description)
                        <p class="mt-1 line-clamp-2 text-[11px] text-gray-400">{{ $researchClass->description }}</p>
                    @endif
                </div>
                <div class="mt-5 grid grid-cols-3 gap-2 border-t border-gray-100 pt-4 text-center">
                    <div>
                        <p class="text-sm font-bold text-gray-800">{{ $researchClass->active_students_count }}</p>
                        <p class="text-[9px] uppercase text-gray-400">Students</p>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">{{ $researchClass->max_students }}</p>
                        <p class="text-[9px] uppercase text-gray-400">Limit</p>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-800">{{ $researchClass->is_active ? 'Active' : 'Closed' }}</p>
                        <p class="text-[9px] uppercase text-gray-400">Status</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-gray-200 bg-white p-12 text-center">
                <i class="ph ph-chalkboard-teacher text-4xl text-gray-300"></i>
                <h2 class="mt-3 text-sm font-bold text-gray-700">No classes yet</h2>
                <p class="mt-1 text-xs text-gray-500">Create the Capstone II class to generate its student join code.</p>
            </div>
        @endforelse
    </div>

    <div x-show="showCreateClassModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-xs">
        <div @click.away="showCreateClassModal = false" class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-bold text-gray-800">Create New Class</h2>
                    <p class="mt-1 text-xs text-gray-500">A secure join code will be generated automatically.</p>
                </div>
                <button type="button" @click="showCreateClassModal = false" class="text-lg text-gray-400 hover:text-gray-600">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('facilitator.classes.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="creation_token" value="{{ old('creation_token', (string) Illuminate\Support\Str::uuid()) }}">

                <div>
                    <label for="facilitator_class_name" class="mb-1.5 block text-xs font-bold text-gray-600">Class name</label>
                    <input id="facilitator_class_name" name="name" type="text" value="{{ old('name') }}" minlength="3" maxlength="120" required placeholder="e.g. ITCAP 102 - IT4A" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-xs focus:border-[#0e5c3a] focus:outline-none">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="facilitator_class_description" class="mb-1.5 block text-xs font-bold text-gray-600">Description</label>
                    <textarea id="facilitator_class_description" name="description" rows="3" maxlength="1000" placeholder="e.g. Capstone Project II" class="w-full resize-none rounded-xl border border-gray-200 px-4 py-2.5 text-xs focus:border-[#0e5c3a] focus:outline-none">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="facilitator_class_limit" class="mb-1.5 block text-xs font-bold text-gray-600">Student limit</label>
                    <input id="facilitator_class_limit" name="max_students" type="number" value="{{ old('max_students', 50) }}" min="1" max="100" required class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-xs focus:border-[#0e5c3a] focus:outline-none">
                    @error('max_students') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showCreateClassModal = false" class="rounded-xl bg-gray-100 px-4 py-2 text-xs font-bold text-gray-700">Cancel</button>
                    <button type="submit" class="rounded-xl bg-[#0e5c3a] px-4 py-2 text-xs font-bold text-white shadow-md hover:bg-[#0a4a2e]">Create Class</button>
                </div>
            </form>
        </div>
    </div>
</section>
