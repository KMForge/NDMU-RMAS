<section class="space-y-8">
    <!-- Header Row -->
    <div>
        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">
            <span>Facilitator Portal</span>
            <span>/</span>
            <span class="text-[#0e5c3a]">Join Requests</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black font-heading text-slate-900 tracking-tight">
            Student Join Requests
        </h1>
        <p class="mt-1 text-xs text-slate-500 font-medium">
            Review and verify student enrollment requests before admitting them to Capstone classes.
        </p>
    </div>

    <!-- Flash Messages -->
    @if (session('join_request_success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs sm:text-sm font-semibold text-emerald-800 flex items-center gap-2.5 animate-fade-in" role="status">
            <i class="ph ph-check-circle text-base text-emerald-600 shrink-0"></i>
            <span>{{ session('join_request_success') }}</span>
        </div>
    @endif
    @if ($errors->has('join_request'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs sm:text-sm font-semibold text-rose-700 flex items-center gap-2.5 animate-fade-in" role="alert">
            <i class="ph ph-warning-circle text-base text-rose-600 shrink-0"></i>
            <span>{{ $errors->first('join_request') }}</span>
        </div>
    @endif

    <!-- 4-Stat Metric Row -->
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @php
            $stats = [
                ['label' => 'Pending Review', 'value' => $classRequestStats['pending'] ?? 0, 'color' => 'from-amber-400 to-amber-600', 'badge' => 'bg-amber-50 border-amber-200 text-amber-800', 'icon' => 'ph-clock'],
                ['label' => 'Approved Students', 'value' => $classRequestStats['approved'] ?? 0, 'color' => 'from-[#073823] to-[#0e5c3a]', 'badge' => 'bg-emerald-50 border-emerald-200 text-[#0e5c3a]', 'icon' => 'ph-check-circle'],
                ['label' => 'Rejected Requests', 'value' => $classRequestStats['rejected'] ?? 0, 'color' => 'from-rose-500 to-rose-700', 'badge' => 'bg-rose-50 border-rose-200 text-rose-700', 'icon' => 'ph-x-circle'],
                ['label' => 'Total Submissions', 'value' => $classRequestStats['total'] ?? 0, 'color' => 'from-slate-700 to-slate-900', 'badge' => 'bg-slate-100 border-slate-200 text-slate-700', 'icon' => 'ph-users-three'],
            ];
        @endphp

        @foreach ($stats as $stat)
            <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">{{ $stat['label'] }}</span>
                    <span class="mt-1 text-2xl sm:text-3xl font-black font-heading text-slate-900 block leading-none">{{ $stat['value'] }}</span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $stat['color'] }} text-white flex items-center justify-center text-2xl shadow-md shadow-slate-900/10 shrink-0">
                    <i class="ph {{ $stat['icon'] }}"></i>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Search & Filter Toolbar -->
    <form method="GET" action="{{ route('facilitator.dashboard') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm md:flex-row md:items-center">
        <input type="hidden" name="tab" value="join-requests">
        <div class="relative flex-1">
            <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input
                type="search"
                name="request_q"
                value="{{ $requestSearch }}"
                maxlength="100"
                placeholder="Search by student name, ID number, email, or class section..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-white focus:bg-white py-2.5 pl-9 pr-4 text-xs font-medium text-slate-900 placeholder-slate-400 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all"
            >
        </div>
        <select
            name="request_status"
            class="rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-white focus:bg-white px-4 py-2.5 text-xs font-bold text-slate-700 focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/10 focus:outline-none transition-all cursor-pointer"
        >
            <option value="pending" @selected($requestStatus === 'pending')>Status: Pending</option>
            <option value="active" @selected($requestStatus === 'active')>Status: Approved</option>
            <option value="rejected" @selected($requestStatus === 'rejected')>Status: Rejected</option>
            <option value="all" @selected($requestStatus === 'all')>All Statuses</option>
        </select>
        <button
            type="submit"
            class="rounded-xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 px-5 py-2.5 text-xs font-black text-white shadow-sm hover:shadow transition-all cursor-pointer"
        >
            Apply Filters
        </button>
    </form>

    <!-- Requests List -->
    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
        @forelse ($classJoinRequests as $joinRequest)
            @php($student = $joinRequest->student)
            <article class="flex flex-col gap-4 border-b border-slate-100 p-5 sm:p-6 last:border-b-0 lg:flex-row lg:items-center lg:justify-between hover:bg-slate-50/60 transition-colors">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#073823] to-[#0e5c3a] font-black text-white text-base shadow-sm">
                        {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($student?->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="min-w-0 space-y-0.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-sm font-black text-slate-900">{{ $student?->name ?? 'Student Account' }}</h2>
                            @if ($student?->student_id)
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 font-mono text-[10px] font-bold text-slate-700">
                                    {{ $student->student_id }}
                                </span>
                            @endif
                        </div>
                        <p class="truncate text-xs text-slate-500 font-medium">{{ $student?->email ?? 'Email unavailable' }}</p>
                        <p class="text-xs font-bold text-[#0e5c3a] flex items-center gap-1.5 pt-0.5">
                            <i class="ph ph-chalkboard-teacher"></i>
                            <span>Target Class: {{ $joinRequest->researchClass?->name ?? 'Deleted class' }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4 lg:justify-end shrink-0">
                    <div class="text-left lg:text-right space-y-0.5">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $joinRequest->status === 'pending' ? 'bg-amber-50 text-amber-800 border border-amber-200' : ($joinRequest->status === 'active' ? 'bg-emerald-50 text-[#0e5c3a] border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $joinRequest->status === 'pending' ? 'bg-amber-500 animate-pulse' : ($joinRequest->status === 'active' ? 'bg-emerald-600' : 'bg-rose-600') }}"></span>
                            {{ $joinRequest->status === 'active' ? 'Approved' : Illuminate\Support\Str::headline($joinRequest->status) }}
                        </span>
                        <p class="text-[10px] font-bold text-slate-400">Requested {{ $joinRequest->requested_at?->diffForHumans() ?? 'recently' }}</p>
                    </div>

                    @if ($joinRequest->status === 'pending')
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('facilitator.classes.join-requests.reject', [$joinRequest->researchClass, $joinRequest]) }}" onsubmit="return confirm('Reject this student join request?')">
                                @csrf @method('PATCH')
                                <button
                                    type="submit"
                                    class="rounded-xl border border-rose-200 bg-rose-50/80 hover:bg-rose-100 text-rose-700 px-3.5 py-2 text-xs font-bold transition-colors cursor-pointer"
                                >
                                    Reject
                                </button>
                            </form>
                            <form method="POST" action="{{ route('facilitator.classes.join-requests.approve', [$joinRequest->researchClass, $joinRequest]) }}">
                                @csrf @method('PATCH')
                                <button
                                    type="submit"
                                    class="rounded-xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] hover:brightness-110 text-white px-4 py-2 text-xs font-black shadow-sm transition-all cursor-pointer flex items-center gap-1.5"
                                >
                                    <i class="ph ph-check-bold"></i>
                                    <span>Approve</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="p-12 text-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto">
                    <i class="ph ph-user-plus"></i>
                </div>
                <h2 class="text-base font-black text-slate-900">No Student Join Requests</h2>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Student enrollment requests for your Capstone classes will appear here for verification.
                </p>
            </div>
        @endforelse
    </div>
</section>
