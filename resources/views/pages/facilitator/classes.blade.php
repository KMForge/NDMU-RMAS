<section class="space-y-8" x-data="{ showCreateClassModal: @js($errors->hasAny(['class', 'creation_token', 'name', 'description', 'max_students'])), copiedJoinCode: null }">
    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">
                <span>Facilitator Portal</span>
                <span>/</span>
                <span class="text-[#0e5c3a]">Capstone Classes</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black font-heading text-slate-900 tracking-tight">
                Capstone Classes
            </h1>
            <p class="mt-1 text-xs text-slate-500 font-medium">
                Manage your academic classes, track cohort capacity, and view approved student rosters.
            </p>
        </div>
        <button
            type="button"
            @click="showCreateClassModal = true"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#073823] via-[#0e5c3a] to-[#0a462c] hover:brightness-110 px-5 py-3 text-xs font-black text-white shadow-md shadow-emerald-950/20 hover:shadow-lg transition-all duration-200 cursor-pointer shrink-0"
        >
            <i class="ph ph-plus-circle text-base text-[#eebc3f]"></i>
            <span>Create New Class</span>
        </button>
    </div>

    <!-- Flash Messages -->
    @if (session('class_success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs sm:text-sm font-semibold text-emerald-800 flex items-center gap-2.5 animate-fade-in" role="status">
            <i class="ph ph-check-circle text-base text-emerald-600 shrink-0"></i>
            <span>{{ session('class_success') }}</span>
        </div>
    @endif

    @if ($errors->has('class'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs sm:text-sm font-semibold text-rose-700 flex items-center gap-2.5 animate-fade-in" role="alert">
            <i class="ph ph-warning-circle text-base text-rose-600 shrink-0"></i>
            <span>{{ $errors->first('class') }}</span>
        </div>
    @endif

    <!-- Classes Grid -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($researchClasses as $researchClass)
            @php
                $activeCount = (int) $researchClass->active_students_count;
                $maxLimit = (int) ($researchClass->max_students ?: 50);
                $percent = $maxLimit > 0 ? min(100, round(($activeCount / $maxLimit) * 100)) : 0;
                $joinCode = rescue(fn () => $researchClass->revealJoinCode(), null, report: false);
            @endphp
            <div class="group relative rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#0e5c3a]/40 hover:shadow-xl hover:shadow-emerald-950/5 flex flex-col justify-between overflow-hidden">
                <!-- Top Brand Accent Stripe -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

                <div>
                    <!-- Join Code Pill & Active Badge Row -->
                    <div class="flex items-center justify-between gap-3 mb-4">
                        @if ($joinCode)
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 shadow-2xs">
                                <span class="text-[9px] font-extrabold uppercase tracking-wider text-amber-700">Code:</span>
                                <code class="font-mono text-xs font-black tracking-widest text-[#073823]">{{ $joinCode }}</code>
                                <button
                                    type="button"
                                    @click="navigator.clipboard?.writeText(@js($joinCode)).then(() => { copiedJoinCode = @js($joinCode); setTimeout(() => copiedJoinCode = null, 2000) })"
                                    class="text-slate-400 hover:text-[#0e5c3a] transition-colors cursor-pointer"
                                    title="Copy Join Code"
                                >
                                    <i class="ph" :class="copiedJoinCode === @js($joinCode) ? 'ph-check text-emerald-600 font-bold' : 'ph-copy'"></i>
                                </button>
                            </div>
                        @else
                            <span class="text-[10px] font-bold text-slate-400">No join code</span>
                        @endif

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $researchClass->is_active ? 'bg-emerald-50 text-[#0e5c3a] border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $researchClass->is_active ? 'bg-[#0e5c3a] animate-pulse' : 'bg-slate-400' }}"></span>
                            {{ $researchClass->is_active ? 'Active' : 'Closed' }}
                        </span>
                    </div>

                    <!-- Class Name & Description -->
                    <div class="space-y-1.5">
                        <h2 class="text-base sm:text-lg font-black font-heading text-slate-900 tracking-tight group-hover:text-[#0e5c3a] transition-colors line-clamp-1">
                            {{ $researchClass->name }}
                        </h2>
                        @if ($researchClass->description)
                            <p class="text-xs text-slate-500 font-medium line-clamp-2 leading-relaxed">
                                {{ $researchClass->description }}
                            </p>
                        @else
                            <p class="text-xs text-slate-400 italic">No description provided for this class section.</p>
                        @endif
                    </div>

                    <!-- Capacity Progress Bar -->
                    <div class="mt-5 space-y-1.5">
                        <div class="flex items-center justify-between text-[11px] font-bold">
                            <span class="text-slate-500">Student Capacity</span>
                            <span class="text-slate-800">{{ $activeCount }} <span class="text-slate-400">/ {{ $maxLimit }}</span> ({{ $percent }}%)</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-[#073823] via-[#0e5c3a] to-[#eebc3f] transition-all duration-500"
                                style="width: {{ $percent }}%"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Footer Stats & CTA Link -->
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-xs text-slate-600">
                        <div class="flex items-center gap-1.5 font-bold">
                            <i class="ph ph-users text-sm text-[#0e5c3a]"></i>
                            <span>{{ $activeCount }} Enrolled</span>
                        </div>
                    </div>

                    <a
                        href="{{ route('facilitator.classes.show', $researchClass) }}"
                        class="inline-flex items-center gap-1.5 text-xs font-black text-[#0e5c3a] hover:text-[#073823] group/btn"
                    >
                        <span>Manage Class</span>
                        <i class="ph ph-arrow-right transition-transform group-hover/btn:translate-x-1"></i>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-3xl border-2 border-dashed border-slate-200 bg-white p-12 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-[#0e5c3a] flex items-center justify-center text-3xl mx-auto shadow-2xs">
                    <i class="ph ph-chalkboard-teacher"></i>
                </div>
                <h2 class="text-base font-black text-slate-900">No Capstone Classes Found</h2>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Create a new Capstone class section to generate student join codes and manage research groups.
                </p>
                <button
                    type="button"
                    @click="showCreateClassModal = true"
                    class="mt-2 inline-flex items-center gap-2 rounded-xl bg-[#0e5c3a] px-4.5 py-2.5 text-xs font-black text-white hover:bg-[#073823] transition-colors cursor-pointer"
                >
                    <i class="ph ph-plus-circle text-base text-[#eebc3f]"></i>
                    <span>Create Your First Class</span>
                </button>
            </div>
        @endforelse
    </div>

    <!-- Create Class Modal -->
    <div x-show="showCreateClassModal" x-transition x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
        <div @click.away="showCreateClassModal = false" class="w-full max-w-md rounded-3xl bg-white p-6 sm:p-7 shadow-2xl space-y-5 border border-slate-200 relative overflow-hidden">
            <!-- Top Accent Stripe -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-black font-heading text-slate-900 tracking-tight">Create New Class</h2>
                    <p class="mt-0.5 text-xs text-slate-500 font-medium">A secure student join code will be generated automatically.</p>
                </div>
                <button
                    type="button"
                    @click="showCreateClassModal = false"
                    class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors flex items-center justify-center text-base cursor-pointer"
                >
                    <i class="ph ph-x font-bold"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('facilitator.classes.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="creation_token" value="{{ old('creation_token', (string) Illuminate\Support\Str::uuid()) }}">

                <div class="space-y-1.5">
                    <label for="facilitator_class_name" class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block">Class Name</label>
                    <input
                        id="facilitator_class_name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        minlength="3"
                        maxlength="120"
                        required
                        placeholder="e.g. ITCAP 102 - BSIT 4A (AY 2026-2027)"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white focus:bg-white px-4 py-2.5 text-xs font-medium text-slate-900 placeholder-slate-400 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all"
                    >
                    @error('name') <p class="text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="facilitator_class_description" class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block">Description (Optional)</label>
                    <textarea
                        id="facilitator_class_description"
                        name="description"
                        rows="3"
                        maxlength="1000"
                        placeholder="e.g. Capstone Project II - Research Implementation and Defense"
                        class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white focus:bg-white px-4 py-2.5 text-xs font-medium text-slate-900 placeholder-slate-400 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all"
                    >{{ old('description') }}</textarea>
                    @error('description') <p class="text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="facilitator_class_limit" class="text-[11px] font-bold uppercase tracking-wider text-slate-700 block">Student Enrollment Limit</label>
                    <input
                        id="facilitator_class_limit"
                        name="max_students"
                        type="number"
                        value="{{ old('max_students', 50) }}"
                        min="1"
                        max="100"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50/70 hover:bg-white focus:bg-white px-4 py-2.5 text-xs font-medium text-slate-900 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all"
                    >
                    @error('max_students') <p class="text-xs font-semibold text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="showCreateClassModal = false"
                        class="rounded-xl border border-slate-200 bg-slate-100 hover:bg-slate-200 px-4.5 py-2.5 text-xs font-bold text-slate-700 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 px-5 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-950/20 transition-all cursor-pointer"
                    >
                        Create Class
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
