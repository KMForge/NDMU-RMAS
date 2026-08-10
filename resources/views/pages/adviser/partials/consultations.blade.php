<div x-show="activeTab === 'consultation'" x-cloak class="space-y-6">
    <div class="rounded-3xl bg-[#0e5c3a] px-7 py-6 text-white shadow-sm">
        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#eebc3f]">Research Adviser Portal</p>
        <h1 class="mt-2 text-2xl font-black font-heading">Consultation Records</h1>
        <p class="mt-1 text-sm text-white/75">Review requests from your assigned research groups and record consultation outcomes.</p>
    </div>

    @if (session('consultation_success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">
            <i class="ph ph-check-circle text-xl"></i>
            <span>{{ session('consultation_success') }}</span>
        </div>
    @endif

    @if ($errors->has('consultation'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-800">
            <i class="ph ph-warning-circle text-xl"></i>
            <span>{{ $errors->first('consultation') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach ([
            ['label' => 'Pending', 'value' => $consultationStats['pending'] ?? 0, 'color' => 'amber'],
            ['label' => 'Approved', 'value' => $consultationStats['approved'] ?? 0, 'color' => 'blue'],
            ['label' => 'Completed', 'value' => $consultationStats['completed'] ?? 0, 'color' => 'emerald'],
            ['label' => 'Rejected', 'value' => $consultationStats['rejected'] ?? 0, 'color' => 'rose'],
            ['label' => 'Total', 'value' => $consultationStats['total'] ?? 0, 'color' => 'gray'],
        ] as $stat)
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-black text-gray-850">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('adviser.dashboard') }}" class="flex flex-wrap items-center gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <input type="hidden" name="tab" value="consultation">
        <div class="min-w-[220px] flex-1">
            <input
                type="search"
                name="consultation_search"
                value="{{ $consultationSearch ?? '' }}"
                placeholder="Search student, group, or agenda..."
                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-xs focus:border-[#0e5c3a] focus:outline-none"
            >
        </div>
        <select name="consultation_status" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold focus:border-[#0e5c3a] focus:outline-none">
            @foreach ([
                'pending' => 'Pending',
                'reschedule_proposed' => 'Reschedule Proposed',
                'approved' => 'Approved',
                'completed' => 'Completed',
                'rejected' => 'Rejected',
                'cancelled' => 'Cancelled',
                'all' => 'All Statuses',
            ] as $value => $label)
                <option value="{{ $value }}" @selected(($consultationStatus ?? 'pending') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#0a4a2e]">
            Apply
        </button>
    </form>

    <div class="space-y-4">
        @forelse ($consultationRequests as $consultationRequest)
            @php
                $requestStatus = $consultationRequest->status->value;
                $requestMode = $consultationRequest->consultation_mode->value;
                $groupMembers = $consultationRequest->researchClassGroup?->members ?? collect();
            @endphp
            <article class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm" x-data="{ action: null }">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div class="min-w-0 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-black text-gray-850">{{ $consultationRequest->researchClassGroup?->name ?? 'Research Group' }}</h2>
                            <span @class([
                                'rounded-full border px-2.5 py-1 text-[9px] font-black uppercase tracking-wider',
                                'border-amber-200 bg-amber-50 text-amber-700' => $requestStatus === 'pending',
                                'border-violet-200 bg-violet-50 text-violet-700' => $requestStatus === 'reschedule_proposed',
                                'border-blue-200 bg-blue-50 text-blue-700' => $requestStatus === 'approved',
                                'border-emerald-200 bg-emerald-50 text-emerald-700' => $requestStatus === 'completed',
                                'border-rose-200 bg-rose-50 text-rose-700' => $requestStatus === 'rejected',
                                'border-gray-200 bg-gray-50 text-gray-600' => $requestStatus === 'cancelled',
                            ])>{{ $consultationRequest->status->label() }}</span>
                        </div>
                        <p class="text-xs font-semibold text-gray-700">
                            Requested by {{ $consultationRequest->requester?->name ?? 'Student' }}
                        </p>
                        <p class="max-w-3xl whitespace-pre-line text-sm text-gray-600">{{ $consultationRequest->agenda }}</p>
                        <div class="flex flex-wrap gap-x-5 gap-y-2 pt-1 text-[11px] text-gray-500">
                            <span><i class="ph ph-calendar-blank mr-1"></i>{{ $consultationRequest->preferred_at?->format('M j, Y g:i A') }}</span>
                            <span><i class="ph ph-clock mr-1"></i>{{ $consultationRequest->duration_minutes }} minutes</span>
                            <span><i class="ph ph-{{ $requestMode === 'online' ? 'video-camera' : 'map-pin' }} mr-1"></i>{{ $consultationRequest->consultation_mode->label() }}</span>
                        </div>
                    </div>

                    @if (in_array($requestStatus, ['pending', 'reschedule_proposed'], true))
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button type="button" @click="action = action === 'approve' ? null : 'approve'" class="rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white">Approve</button>
                            <button type="button" @click="action = action === 'reschedule' ? null : 'reschedule'" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-xs font-bold text-amber-800">Reschedule</button>
                            <button type="button" @click="action = action === 'reject' ? null : 'reject'" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-700">Reject</button>
                        </div>
                    @elseif ($requestStatus === 'approved')
                        <button type="button" @click="action = action === 'complete' ? null : 'complete'" class="shrink-0 rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white">Record Outcome</button>
                    @endif
                </div>

                <form x-show="action === 'approve'" x-cloak method="POST" action="{{ route('adviser.consultations.approve', $consultationRequest) }}" class="mt-5 grid gap-3 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 md:grid-cols-2">
                    @csrf
                    <input type="datetime-local" name="confirmed_start_at" value="{{ $consultationRequest->preferred_at?->format('Y-m-d\TH:i') }}" class="rounded-xl border border-gray-200 px-3 py-2.5 text-xs" aria-label="Confirmed consultation time">
                    <select name="duration_minutes" class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs" aria-label="Duration">
                        @foreach ([30, 45, 60] as $minutes)
                            <option value="{{ $minutes }}" @selected($consultationRequest->duration_minutes === $minutes)>{{ $minutes }} minutes</option>
                        @endforeach
                    </select>
                    @if ($requestMode === 'online')
                        <input type="url" name="meeting_url" placeholder="Secure meeting URL" class="rounded-xl border border-gray-200 px-3 py-2.5 text-xs md:col-span-2">
                    @else
                        <input type="text" name="location" maxlength="500" placeholder="Consultation location" class="rounded-xl border border-gray-200 px-3 py-2.5 text-xs md:col-span-2">
                    @endif
                    <textarea name="review_notes" maxlength="2000" rows="2" placeholder="Optional notes for the group" class="rounded-xl border border-gray-200 p-3 text-xs md:col-span-2"></textarea>
                    <button type="submit" class="rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white md:col-span-2">Confirm Consultation</button>
                </form>

                <form x-show="action === 'reschedule'" x-cloak method="POST" action="{{ route('adviser.consultations.propose-reschedule', $consultationRequest) }}" class="mt-5 grid gap-3 rounded-2xl border border-amber-100 bg-amber-50/50 p-4 md:grid-cols-2">
                    @csrf
                    <input type="datetime-local" name="proposed_start_at" required class="rounded-xl border border-gray-200 px-3 py-2.5 text-xs" aria-label="Proposed consultation time">
                    <select name="duration_minutes" class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs" aria-label="Duration">
                        @foreach ([30, 45, 60] as $minutes)
                            <option value="{{ $minutes }}" @selected($consultationRequest->duration_minutes === $minutes)>{{ $minutes }} minutes</option>
                        @endforeach
                    </select>
                    <textarea name="reason" maxlength="1000" rows="2" placeholder="Reason for the proposed schedule" class="rounded-xl border border-gray-200 p-3 text-xs md:col-span-2"></textarea>
                    <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-bold text-white md:col-span-2">Send Schedule Proposal</button>
                </form>

                <form x-show="action === 'reject'" x-cloak method="POST" action="{{ route('adviser.consultations.reject', $consultationRequest) }}" class="mt-5 space-y-3 rounded-2xl border border-rose-100 bg-rose-50/50 p-4">
                    @csrf
                    <textarea name="reason" required minlength="3" maxlength="2000" rows="3" placeholder="Explain why this request is being rejected" class="w-full rounded-xl border border-gray-200 p-3 text-xs"></textarea>
                    <button type="submit" class="w-full rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white">Reject Request</button>
                </form>

                <form x-show="action === 'complete'" x-cloak method="POST" action="{{ route('adviser.consultations.complete', $consultationRequest) }}" class="mt-5 space-y-3 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4">
                    @csrf
                    <textarea name="discussion" required minlength="5" maxlength="5000" rows="3" placeholder="Consultation discussion and outcomes" class="w-full rounded-xl border border-gray-200 p-3 text-xs"></textarea>
                    <textarea name="recommendations" maxlength="5000" rows="2" placeholder="Recommendations or next steps" class="w-full rounded-xl border border-gray-200 p-3 text-xs"></textarea>
                    @if ($groupMembers->isNotEmpty())
                        <fieldset class="space-y-2">
                            <legend class="text-[10px] font-black uppercase tracking-wider text-gray-500">Students who attended</legend>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($groupMembers as $member)
                                    <label class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold">
                                        <input type="checkbox" name="attendees[]" value="{{ $member->student_id }}" class="rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                                        <span>{{ $member->student?->name ?? 'Student' }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                    <button type="submit" class="w-full rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white">Save Official Consultation Record</button>
                </form>
            </article>
        @empty
            <div class="rounded-3xl border border-gray-100 bg-white p-12 text-center shadow-sm">
                <i class="ph ph-chats-teardrop text-4xl text-gray-300"></i>
                <h2 class="mt-3 text-sm font-black text-gray-800">No consultation requests found</h2>
                <p class="mt-1 text-xs text-gray-500">Requests from students in your assigned research groups will appear here.</p>
            </div>
        @endforelse
    </div>

    @if ($consultationRequests->hasPages())
        <div>{{ $consultationRequests->links() }}</div>
    @endif
</div>
