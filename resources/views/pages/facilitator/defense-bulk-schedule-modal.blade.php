<!-- Bulk Defense Scheduling Modal -->
<div
    x-show="showBulkScheduleModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="bulk-schedule-modal-title"
>
    <!-- Backdrop -->
    <div
        x-show="showBulkScheduleModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
        @click="showBulkScheduleModal = false"
    ></div>

    <!-- Modal Content -->
    <div
        x-show="showBulkScheduleModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full max-w-6xl rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/10 overflow-hidden my-8 z-10"
        @click.stop
    >
        <!-- Top Accent Bar -->
        <div class="h-2 w-full bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

        <!-- Header -->
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5 sm:px-8">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-800">
                        <i class="ph ph-calendar-plus text-lg"></i>
                    </span>
                    <h2 id="bulk-schedule-modal-title" class="text-xl font-black font-heading text-slate-900">
                        Bulk Defense Scheduling &amp; Session Order
                    </h2>
                </div>
                <p class="text-xs text-slate-500 max-w-3xl">
                    Schedule an entire session window (e.g. 7:00 AM – 6:00 PM) for multiple research groups. Defense committees are automatically loaded from class assignments. Arrange presentation sequence manually using order controls.
                </p>
            </div>
            <button
                type="button"
                @click="showBulkScheduleModal = false"
                class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors cursor-pointer"
            >
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 sm:p-8 space-y-6 max-h-[78vh] overflow-y-auto">
            <!-- Top Controls Grid: Class, Stage, Room, Date & Shared Session Window -->
            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Class Selection -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Research Class <span class="text-rose-500">*</span>
                        </label>
                        <select
                            x-model="bulkScheduleForm.classId"
                            @change="onBulkClassChange()"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none"
                        >
                            <template x-for="rc in facilitatorClasses" :key="'bulk-rc-' + rc.id">
                                <option :value="rc.id" x-text="rc.name + ' (' + rc.code + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Defense Stage -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Defense Stage <span class="text-rose-500">*</span>
                        </label>
                        <select
                            x-model="bulkScheduleForm.type"
                            @change="loadBulkGroups()"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none"
                        >
                            <option value="title_presentation">Title Presentation</option>
                            <option value="proposal_defense">Proposal Defense</option>
                            <option value="final_defense">Final Defense</option>
                        </select>
                    </div>

                    <!-- Venue / Room -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Presentation Venue <span class="text-rose-500">*</span>
                        </label>
                        <select
                            x-model="bulkScheduleForm.roomId"
                            @change="checkBulkConflicts()"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none"
                        >
                            <option value="">-- Select Room --</option>
                            <template x-for="r in defenseRooms" :key="'bulk-rm-' + r.id">
                                <option :value="r.id" x-text="r.name + (r.code ? ' (' + r.code + ')' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Session Date -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Defense Date <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="date"
                            x-model="bulkScheduleForm.sessionDate"
                            @change="checkBulkConflicts()"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none"
                        >
                    </div>
                </div>

                <!-- Shared Session Window Banner -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-3 border-t border-slate-200/70">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl bg-emerald-100/70 text-[#0e5c3a] flex items-center justify-center shrink-0">
                            <i class="ph ph-clock-countdown text-lg"></i>
                        </div>
                        <div>
                            <span class="text-xs font-black text-slate-800 block">Unified Session Time Window</span>
                            <span class="text-[11px] text-slate-500">All selected research groups present within this block according to presentation sequence.</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div>
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">Start Time</span>
                            <input
                                type="time"
                                x-model="bulkScheduleForm.sessionStartTime"
                                @change="checkBulkConflicts()"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none"
                            >
                        </div>
                        <span class="text-slate-400 font-black mt-4">to</span>
                        <div>
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-0.5">End Time</span>
                            <input
                                type="time"
                                x-model="bulkScheduleForm.sessionEndTime"
                                @change="checkBulkConflicts()"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conflicts Alert Box -->
            <div
                x-show="bulkConflictErrors.length > 0"
                class="rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-xs text-rose-900 space-y-2"
            >
                <div class="flex items-center gap-2 font-black text-rose-800">
                    <i class="ph ph-warning-octagon text-lg text-rose-600"></i>
                    <span>Schedule Conflicts Detected</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-[11px] text-rose-800">
                    <template x-for="(err, idx) in bulkConflictErrors" :key="'bulk-err-' + idx">
                        <li x-text="err"></li>
                    </template>
                </ul>
            </div>

            <!-- Missing Committees Warning Box -->
            <div
                x-show="groupsWithMissingCommittees.length > 0"
                class="rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-xs text-amber-900 space-y-1.5"
            >
                <div class="flex items-center gap-2 font-black text-amber-800">
                    <i class="ph ph-warning text-lg text-amber-600"></i>
                    <span>Missing Committee Evaluators Detected</span>
                </div>
                <p class="text-[11px] text-amber-800">
                    <span class="font-bold" x-text="groupsWithMissingCommittees.length"></span> of your selected groups do not have assigned panel evaluators. Use "Assign Class Panels" first or customize them before scheduling.
                </p>
                <div class="pt-1 flex flex-wrap gap-1.5">
                    <template x-for="g in groupsWithMissingCommittees" :key="'missing-badge-' + g.id">
                        <span class="px-2 py-0.5 rounded-md bg-amber-200/70 text-amber-900 text-[10px] font-bold" x-text="g.group_name"></span>
                    </template>
                </div>
            </div>

            <!-- Main Interactive Section: Group Selection & Presentation Order -->
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                            <i class="ph ph-list-numbers text-base text-[#0e5c3a]"></i>
                            <span>Research Groups &amp; Presentation Sequence</span>
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Select research groups to include in this session. Reorder rows to set their official presentation sequence.
                        </p>
                    </div>

                    <!-- Toolbar Actions -->
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="selectAllBulkGroups()"
                            class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-[11px] font-bold text-slate-700 hover:bg-slate-50 cursor-pointer"
                        >
                            Select All
                        </button>
                        <button
                            type="button"
                            @click="deselectAllBulkGroups()"
                            class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-[11px] font-bold text-slate-500 hover:bg-slate-50 cursor-pointer"
                        >
                            Deselect
                        </button>
                        <button
                            type="button"
                            @click="resetBulkOrder()"
                            class="px-2.5 py-1 rounded-lg border border-emerald-200 bg-emerald-50 text-[11px] font-bold text-[#0e5c3a] hover:bg-emerald-100 cursor-pointer"
                            title="Reset presentation order to default"
                        >
                            Reset Order
                        </button>
                    </div>
                </div>

                <!-- Groups Table / Sequence List -->
                <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50 text-[10px] font-black uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-3 w-12 text-center">Include</th>
                                <th class="py-3 px-3 w-20 text-center">Seq #</th>
                                <th class="py-3 px-4">Research Group &amp; Study</th>
                                <th class="py-3 px-4">Adviser</th>
                                <th class="py-3 px-4">Committee Evaluators (Chair &bull; Panel 1 &bull; Panel 2)</th>
                                <th class="py-3 px-3 w-28 text-center">Order Controls</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-if="bulkClassGroups.length === 0">
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-slate-500 italic">
                                        No research groups available for defense scheduling in this class.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(grp, idx) in bulkClassGroups" :key="'bulk-row-' + grp.id">
                                <tr
                                    class="transition-colors"
                                    :class="{
                                        'bg-emerald-50/40': grp.selected,
                                        'hover:bg-slate-50/80': !grp.selected
                                    }"
                                >
                                    <!-- Select Checkbox -->
                                    <td class="py-3 px-3 text-center">
                                        <input
                                            type="checkbox"
                                            x-model="grp.selected"
                                            class="rounded text-[#0e5c3a] focus:ring-[#0e5c3a] cursor-pointer"
                                        >
                                    </td>

                                    <!-- Sequence Number -->
                                    <td class="py-3 px-3 text-center">
                                        <template x-if="grp.selected">
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-[#0e5c3a] font-black text-[11px] shadow-2xs" x-text="selectedOrderNumber(grp)"></span>
                                        </template>
                                        <template x-if="!grp.selected">
                                            <span class="text-slate-300 font-bold text-[10px]">&mdash;</span>
                                        </template>
                                    </td>

                                    <!-- Group Details -->
                                    <td class="py-3 px-4">
                                        <div class="font-black text-slate-900" x-text="grp.group_name"></div>
                                        <div class="text-[11px] text-slate-500 truncate max-w-sm" x-text="grp.title || 'No Approved Proposal Title'"></div>
                                    </td>

                                    <!-- Adviser -->
                                    <td class="py-3 px-4 text-slate-600 text-[11px]">
                                        <span class="font-medium" x-text="grp.adviser_name || 'None'"></span>
                                    </td>

                                    <!-- Panel Committee -->
                                    <td class="py-3 px-4">
                                        <template x-if="grp.committee && grp.committee.chairperson_name">
                                            <div class="space-y-0.5 text-[11px]">
                                                <div class="flex items-center gap-1.5 text-slate-800 font-semibold">
                                                    <span class="text-[9px] font-black uppercase text-amber-700 bg-amber-100/80 px-1.5 py-0.2 rounded">Chair</span>
                                                    <span x-text="grp.committee.chairperson_name"></span>
                                                </div>
                                                <div class="flex items-center gap-1 text-[10px] text-slate-500">
                                                    <span>1:</span>
                                                    <span class="font-medium text-slate-700" x-text="grp.committee.panel_member_1_name"></span>
                                                    <span class="text-slate-300">&bull;</span>
                                                    <span>2:</span>
                                                    <span class="font-medium text-slate-700" x-text="grp.committee.panel_member_2_name"></span>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!grp.committee || !grp.committee.chairperson_name">
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full">
                                                <i class="ph ph-warning-circle"></i>
                                                <span>Missing Committee</span>
                                            </span>
                                        </template>
                                    </td>

                                    <!-- Reordering Controls -->
                                    <td class="py-3 px-3 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="moveBulkGroupUp(idx)"
                                                :disabled="idx === 0"
                                                class="h-7 w-7 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center justify-center transition cursor-pointer"
                                                title="Move up in presentation sequence"
                                            >
                                                <i class="ph ph-caret-up text-sm font-bold"></i>
                                            </button>
                                            <button
                                                type="button"
                                                @click="moveBulkGroupDown(idx)"
                                                :disabled="idx === bulkClassGroups.length - 1"
                                                class="h-7 w-7 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 disabled:opacity-30 disabled:cursor-not-allowed flex items-center justify-center transition cursor-pointer"
                                                title="Move down in presentation sequence"
                                            >
                                                <i class="ph ph-caret-down text-sm font-bold"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/80 px-6 py-4 sm:px-8">
            <div class="text-xs text-slate-500">
                <span>Selected: </span>
                <strong class="text-slate-900" x-text="bulkSelectedCount">0</strong> groups &bull;
                <span>Session: </span>
                <strong class="text-slate-900" x-text="bulkScheduleForm.sessionDate || 'No Date'"></strong>
                (<span x-text="bulkScheduleForm.sessionStartTime"></span> - <span x-text="bulkScheduleForm.sessionEndTime"></span>)
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="showBulkScheduleModal = false"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="submitBulkSchedule()"
                    :disabled="isSubmittingBulkSchedule || bulkSelectedCount === 0 || !bulkScheduleForm.roomId || !bulkScheduleForm.sessionDate || groupsWithMissingCommittees.length > 0 || bulkConflictErrors.length > 0"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] px-6 py-2.5 text-xs font-black text-white shadow-md hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
                >
                    <i x-show="!isSubmittingBulkSchedule" class="ph ph-calendar-check text-base text-[#eebc3f]"></i>
                    <i x-show="isSubmittingBulkSchedule" class="ph ph-spinner animate-spin text-base"></i>
                    <span x-text="isSubmittingBulkSchedule ? 'Scheduling Defenses...' : 'Schedule ' + bulkSelectedCount + ' Defenses'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
