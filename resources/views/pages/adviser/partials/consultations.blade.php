<div x-show="activeTab === 'consultation'" x-cloak class="space-y-6">
    @if (session('consultation_success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800 shadow-xs">
            <i class="ph ph-check-circle text-xl text-emerald-600"></i>
            <span>{{ session('consultation_success') }}</span>
        </div>
    @endif

    @if ($errors->has('consultation'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-800 shadow-xs">
            <i class="ph ph-warning-circle text-xl text-rose-600"></i>
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
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-xs transition-all hover:shadow-md">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-black text-gray-900">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('adviser.dashboard') }}" class="flex flex-wrap items-center gap-3 rounded-2xl border border-gray-100 bg-white p-5 shadow-xs">
        <input type="hidden" name="tab" value="consultation">
        <div class="min-w-[240px] flex-1">
            <input
                type="search"
                name="consultation_search"
                value="{{ $consultationSearch ?? '' }}"
                placeholder="Search student, group, or agenda..."
                class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm text-gray-800 focus:border-[#0e5c3a] focus:outline-none"
            >
        </div>
        <select name="consultation_status" class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 focus:border-[#0e5c3a] focus:outline-none">
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
        <button type="submit" class="rounded-xl bg-[#0e5c3a] px-6 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-xs hover:bg-[#0a4a2e] transition-colors">
            Apply Filter
        </button>
    </form>

    <div class="space-y-4">
        @forelse ($consultationRequests as $consultationRequest)
            @php
                $requestStatus = $consultationRequest->status->value;
                $requestMode = $consultationRequest->consultation_mode->value;
                $groupMembers = $consultationRequest->researchClassGroup?->members ?? collect();
            @endphp
            <article class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-xs transition-all hover:shadow-md" x-data="{ action: null }">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div class="min-w-0 space-y-2.5">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-lg font-bold text-gray-900 leading-snug">{{ $consultationRequest->researchClassGroup?->name ?? 'Research Group' }}</h2>
                            <span @class([
                                'rounded-full border px-3.5 py-1 text-xs font-bold uppercase tracking-wider',
                                'border-amber-200 bg-amber-50 text-amber-800' => $requestStatus === 'pending',
                                'border-violet-200 bg-violet-50 text-violet-800' => $requestStatus === 'reschedule_proposed',
                                'border-blue-200 bg-blue-50 text-blue-800' => $requestStatus === 'approved',
                                'border-emerald-200 bg-emerald-50 text-emerald-800' => $requestStatus === 'completed',
                                'border-rose-200 bg-rose-50 text-rose-800' => $requestStatus === 'rejected',
                                'border-gray-200 bg-gray-50 text-gray-700' => $requestStatus === 'cancelled',
                            ])>{{ $consultationRequest->status->label() }}</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <i class="ph ph-user text-gray-400"></i>
                            <span>Requested by {{ $consultationRequest->requester?->name ?? 'Student' }}</span>
                        </p>
                        <div class="pt-1">
                            <p class="max-w-3xl whitespace-pre-line text-sm text-gray-800 leading-relaxed font-normal bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <span class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Agenda</span>
                                {{ $consultationRequest->agenda }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-x-6 gap-y-2 pt-2 text-xs font-medium text-gray-600">
                            <span class="flex items-center gap-1.5"><i class="ph ph-calendar-blank text-gray-500 text-sm"></i>{{ $consultationRequest->preferred_at?->format('M j, Y g:i A') }}</span>
                            <span class="flex items-center gap-1.5"><i class="ph ph-clock text-gray-500 text-sm"></i>{{ $consultationRequest->duration_minutes }} minutes</span>
                            <span class="flex items-center gap-1.5"><i class="ph ph-{{ $requestMode === 'online' ? 'video-camera' : 'map-pin' }} text-gray-500 text-sm"></i>{{ $consultationRequest->consultation_mode->label() }}</span>
                        </div>
                    </div>

                    @if (in_array($requestStatus, ['pending', 'reschedule_proposed'], true))
                        <div class="flex shrink-0 flex-wrap gap-2.5">
                            <button type="button" @click="action = action === 'approve' ? null : 'approve'" class="rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-[#0a4a2e]">Approve &amp; Set Mode</button>
                            <button type="button" @click="action = action === 'reschedule' ? null : 'reschedule'" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-xs font-bold text-amber-900 hover:bg-amber-100">Reschedule</button>
                            <button type="button" @click="action = action === 'reject' ? null : 'reject'" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-bold text-rose-800 hover:bg-rose-100">Reject</button>
                        </div>
                    @elseif ($requestStatus === 'approved')
                        <div class="flex shrink-0 flex-wrap gap-2.5">
                            <button type="button" @click="action = action === 'meeting' ? null : 'meeting'" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-xs font-bold text-blue-800 hover:bg-blue-100">Meeting Details</button>
                            <button type="button" @click="action = action === 'complete' ? null : 'complete'" class="rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-[#0a4a2e]">Record Outcome</button>
                        </div>
                    @endif
                </div>

                <form x-show="action === 'approve'" x-cloak x-data="{ mode: @js($requestMode) }" method="POST" action="{{ route('adviser.consultations.approve', $consultationRequest) }}" class="mt-5 grid gap-4 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 md:grid-cols-2">
                    @csrf
                    <div class="md:col-span-2">
                        <h3 class="text-base font-bold text-emerald-950">Approve Consultation</h3>
                        <p class="mt-1 text-xs font-medium text-emerald-800">Confirm the schedule and choose how the consultation will be conducted.</p>
                    </div>
                    <label class="space-y-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700">Confirmed Date &amp; Time</span>
                        <input type="datetime-local" name="confirmed_start_at" value="{{ $consultationRequest->preferred_at?->format('Y-m-d\TH:i') }}" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 focus:border-[#0e5c3a] focus:outline-none">
                    </label>
                    <label class="space-y-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700">Duration</span>
                        <select name="duration_minutes" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 focus:border-[#0e5c3a] focus:outline-none">
                            @foreach ([30, 45, 60] as $minutes)
                                <option value="{{ $minutes }}" @selected($consultationRequest->duration_minutes === $minutes)>{{ $minutes }} minutes</option>
                            @endforeach
                        </select>
                    </label>
                    <fieldset class="space-y-2 md:col-span-2">
                        <legend class="text-xs font-bold uppercase tracking-wider text-gray-700">Consultation Mode</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <label :class="mode === 'in_person' ? 'border-[#0e5c3a] bg-emerald-100/70 text-[#0e5c3a] ring-1 ring-[#0e5c3a]' : 'border-gray-200 bg-white text-gray-700'" class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 text-sm font-bold transition-colors">
                                <input type="radio" name="consultation_mode" value="in_person" x-model="mode" class="text-[#0e5c3a] focus:ring-[#0e5c3a]">
                                <i class="ph ph-map-pin text-xl"></i>
                                <span>In Person</span>
                            </label>
                            <label :class="mode === 'online' ? 'border-blue-500 bg-blue-100/70 text-blue-800 ring-1 ring-blue-500' : 'border-gray-200 bg-white text-gray-700'" class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 text-sm font-bold transition-colors">
                                <input type="radio" name="consultation_mode" value="online" x-model="mode" class="text-blue-600 focus:ring-blue-500">
                                <i class="ph ph-video-camera text-xl"></i>
                                <span>Online</span>
                            </label>
                        </div>
                    </fieldset>
                    <label x-show="mode === 'online'" class="space-y-1.5 md:col-span-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700">Online Meeting Link</span>
                        <input type="url" name="meeting_url" placeholder="Paste meeting link (e.g. Google Meet or Zoom link)" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 focus:border-[#0e5c3a] focus:outline-none">
                    </label>
                    <label x-show="mode === 'in_person'" class="space-y-1.5 md:col-span-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-700">Consultation Location</span>
                        <input type="text" name="location" maxlength="500" placeholder="Room or venue location" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 focus:border-[#0e5c3a] focus:outline-none">
                    </label>
                    <textarea name="review_notes" maxlength="2000" rows="3" placeholder="Optional notes for the group" class="w-full rounded-xl border border-gray-300 bg-white p-3.5 text-sm text-gray-800 focus:border-[#0e5c3a] focus:outline-none md:col-span-2"></textarea>
                    <button type="submit" class="rounded-xl bg-[#0e5c3a] px-5 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-xs hover:bg-[#0a4a2e] md:col-span-2">Confirm Consultation</button>
                </form>

                <form x-show="action === 'reschedule'" x-cloak method="POST" action="{{ route('adviser.consultations.propose-reschedule', $consultationRequest) }}" class="mt-5 space-y-3 rounded-2xl border border-amber-200 bg-amber-50/60 p-5">
                    @csrf
                    <p class="text-sm font-semibold text-amber-900">Only the proposed date and time will change. The student's agenda remains unchanged.</p>
                    <input type="datetime-local" name="proposed_start_at" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 focus:border-amber-600 focus:outline-none" aria-label="Proposed consultation time">
                    <button type="submit" class="w-full rounded-xl bg-amber-600 px-5 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-xs hover:bg-amber-700">Send New Date and Time</button>
                </form>

                <form x-show="action === 'meeting'" x-cloak x-data="{ mode: @js($requestMode) }" method="POST" action="{{ route('adviser.consultations.meeting-details.update', $consultationRequest) }}" class="mt-5 grid gap-4 rounded-2xl border border-blue-200 bg-blue-50/60 p-5 md:grid-cols-2">
                    @csrf
                    @method('PATCH')
                    <fieldset class="space-y-2 md:col-span-2">
                        <legend class="text-xs font-bold uppercase tracking-wider text-gray-700">Change Consultation Mode</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <label :class="mode === 'in_person' ? 'border-[#0e5c3a] bg-emerald-100/70 text-[#0e5c3a] ring-1 ring-[#0e5c3a]' : 'border-gray-200 bg-white text-gray-700'" class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 text-sm font-bold transition-colors">
                                <input type="radio" name="consultation_mode" value="in_person" x-model="mode" class="text-[#0e5c3a] focus:ring-[#0e5c3a]">
                                <i class="ph ph-map-pin text-xl"></i>
                                <span>In Person</span>
                            </label>
                            <label :class="mode === 'online' ? 'border-blue-500 bg-blue-100/70 text-blue-800 ring-1 ring-blue-500' : 'border-gray-200 bg-white text-gray-700'" class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 text-sm font-bold transition-colors">
                                <input type="radio" name="consultation_mode" value="online" x-model="mode" class="text-blue-600 focus:ring-blue-500">
                                <i class="ph ph-video-camera text-xl"></i>
                                <span>Online</span>
                            </label>
                        </div>
                    </fieldset>
                    <input x-show="mode === 'online'" type="url" name="meeting_url" value="{{ $consultationRequest->meeting_url }}" placeholder="Paste online meeting link" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 focus:border-blue-600 focus:outline-none md:col-span-2">
                    <input x-show="mode === 'in_person'" type="text" name="location" value="{{ $consultationRequest->location }}" maxlength="500" placeholder="Consultation location" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 focus:border-blue-600 focus:outline-none md:col-span-2">
                    <p class="text-xs font-semibold text-blue-800 md:col-span-2">Saved details become visible to the research group.</p>
                    <button type="submit" class="rounded-xl bg-blue-600 px-5 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-xs hover:bg-blue-700 md:col-span-2">Save Meeting Details</button>
                </form>

                <form x-show="action === 'reject'" x-cloak method="POST" action="{{ route('adviser.consultations.reject', $consultationRequest) }}" class="mt-5 space-y-3 rounded-2xl border border-rose-200 bg-rose-50/60 p-5">
                    @csrf
                    <textarea name="reason" required minlength="3" maxlength="2000" rows="3" placeholder="Explain why this request is being rejected" class="w-full rounded-xl border border-gray-300 bg-white p-3.5 text-sm text-gray-800 focus:border-rose-600 focus:outline-none"></textarea>
                    <button type="submit" class="w-full rounded-xl bg-rose-600 px-5 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-xs hover:bg-rose-700">Reject Request</button>
                </form>

                <form x-show="action === 'complete'" x-cloak method="POST" action="{{ route('adviser.consultations.complete', $consultationRequest) }}" class="mt-5 space-y-4 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5">
                    @csrf
                    <textarea name="discussion" required minlength="5" maxlength="5000" rows="3" placeholder="Consultation discussion and outcomes" class="w-full rounded-xl border border-gray-300 bg-white p-3.5 text-sm text-gray-800 focus:border-[#0e5c3a] focus:outline-none"></textarea>
                    <textarea name="recommendations" maxlength="5000" rows="2" placeholder="Recommendations or next steps" class="w-full rounded-xl border border-gray-300 bg-white p-3.5 text-sm text-gray-800 focus:border-[#0e5c3a] focus:outline-none"></textarea>
                    @if ($groupMembers->isNotEmpty())
                        <fieldset class="space-y-2">
                            <legend class="text-xs font-bold uppercase tracking-wider text-gray-700">Students who attended</legend>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($groupMembers as $member)
                                    <label class="flex items-center gap-2.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-bold text-gray-800 cursor-pointer shadow-xs">
                                        <input type="checkbox" name="attendees[]" value="{{ $member->student_id }}" class="h-4 w-4 rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                                        <span>{{ $member->student?->name ?? 'Student' }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                    <button type="submit" class="w-full rounded-xl bg-[#0e5c3a] px-5 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-xs hover:bg-[#0a4a2e]">Save Official Consultation Record</button>
                </form>
            </article>
        @empty
            <div class="rounded-3xl border border-gray-100 bg-white p-12 text-center shadow-xs">
                <i class="ph ph-chats-teardrop text-5xl text-gray-300"></i>
                <h2 class="mt-4 text-base font-bold text-gray-800">No consultation requests found</h2>
                <p class="mt-1 text-sm text-gray-500">Requests from students in your assigned research groups will appear here.</p>
            </div>
        @endforelse
    </div>

    @if ($consultationRequests->hasPages())
        <div>{{ $consultationRequests->links() }}</div>
    @endif
</div>
