<section class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold font-heading text-gray-800">Join Requests</h1>
        <p class="mt-1 text-xs text-gray-500">Review student requests before they enter your Capstone classes.</p>
    </div>

    @if (session('join_request_success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('join_request_success') }}</div>
    @endif
    @if ($errors->has('join_request'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('join_request') }}</div>
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Pending', 'value' => $classRequestStats['pending'] ?? 0, 'color' => 'text-orange-600', 'icon' => 'ph-clock'],
            ['label' => 'Approved', 'value' => $classRequestStats['approved'] ?? 0, 'color' => 'text-emerald-600', 'icon' => 'ph-check-circle'],
            ['label' => 'Rejected', 'value' => $classRequestStats['rejected'] ?? 0, 'color' => 'text-red-600', 'icon' => 'ph-x-circle'],
            ['label' => 'Total', 'value' => $classRequestStats['total'] ?? 0, 'color' => 'text-blue-600', 'icon' => 'ph-users'],
        ] as $stat)
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between"><p class="text-xs font-semibold text-gray-500">{{ $stat['label'] }}</p><i class="ph {{ $stat['icon'] }} text-xl {{ $stat['color'] }}"></i></div>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('facilitator.dashboard') }}" class="flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm md:flex-row">
        <input type="hidden" name="tab" value="join-requests">
        <div class="relative flex-1">
            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="search" name="request_q" value="{{ $requestSearch }}" maxlength="100" placeholder="Search student, ID, email, or class" class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-4 text-xs focus:border-[#0e5c3a] focus:outline-none">
        </div>
        <select name="request_status" class="rounded-xl border border-gray-200 px-4 py-2.5 text-xs focus:border-[#0e5c3a] focus:outline-none">
            <option value="pending" @selected($requestStatus === 'pending')>Pending</option>
            <option value="active" @selected($requestStatus === 'active')>Approved</option>
            <option value="rejected" @selected($requestStatus === 'rejected')>Rejected</option>
            <option value="all" @selected($requestStatus === 'all')>All statuses</option>
        </select>
        <button type="submit" class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white">Filter</button>
    </form>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        @forelse ($classJoinRequests as $joinRequest)
            @php($student = $joinRequest->student)
            <article class="flex flex-col gap-4 border-b border-gray-100 p-5 last:border-b-0 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700">
                        {{ Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($student?->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="truncate text-sm font-bold text-gray-800">{{ $student?->name ?? 'Deleted student account' }}</h2>
                        <p class="mt-0.5 truncate text-[11px] text-gray-500">{{ $student?->student_id ?: 'No student ID' }} · {{ $student?->email ?? 'Email unavailable' }}</p>
                        <p class="mt-1 text-[11px] font-semibold text-[#0e5c3a]">{{ $joinRequest->researchClass?->name ?? 'Deleted class' }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4 lg:justify-end">
                    <div class="text-right">
                        <span class="rounded-full px-3 py-1 text-[9px] font-bold uppercase {{ $joinRequest->status === 'pending' ? 'bg-orange-100 text-orange-700' : ($joinRequest->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700') }}">
                            {{ $joinRequest->status === 'active' ? 'Approved' : Illuminate\Support\Str::headline($joinRequest->status) }}
                        </span>
                        <p class="mt-2 text-[10px] text-gray-400">Requested {{ $joinRequest->requested_at?->diffForHumans() ?? 'recently' }}</p>
                    </div>

                    @if ($joinRequest->status === 'pending')
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('facilitator.classes.join-requests.reject', [$joinRequest->researchClass, $joinRequest]) }}" onsubmit="return confirm('Reject this join request?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50">Reject</button>
                            </form>
                            <form method="POST" action="{{ route('facilitator.classes.join-requests.approve', [$joinRequest->researchClass, $joinRequest]) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="rounded-lg bg-[#0e5c3a] px-3 py-2 text-xs font-bold text-white hover:bg-[#0a4a2e]">Approve</button>
                            </form>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="p-12 text-center">
                <i class="ph ph-user-plus text-4xl text-gray-300"></i>
                <h2 class="mt-3 text-sm font-bold text-gray-700">No requests found</h2>
                <p class="mt-1 text-xs text-gray-500">Student requests matching the selected filters will appear here.</p>
            </div>
        @endforelse
    </div>
</section>
