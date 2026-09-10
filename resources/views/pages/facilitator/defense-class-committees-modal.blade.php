<!-- Class Defense Committees Modal -->
<div
    x-show="showClassCommitteeModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="class-committee-modal-title"
>
    <!-- Backdrop -->
    <div
        x-show="showClassCommitteeModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
        @click="showClassCommitteeModal = false"
    ></div>

    <!-- Modal Content -->
    <div
        x-show="showClassCommitteeModal"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative w-full max-w-5xl rounded-3xl bg-white shadow-2xl ring-1 ring-slate-900/10 overflow-hidden my-8 z-10"
        @click.stop
    >
        <!-- Top Accent Bar -->
        <div class="h-2 w-full bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

        <!-- Header -->
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5 sm:px-8">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-[#0e5c3a]">
                        <i class="ph ph-users-three text-lg"></i>
                    </span>
                    <h2 id="class-committee-modal-title" class="text-xl font-black font-heading text-slate-900">
                        Class Defense Panel Assignment
                    </h2>
                </div>
                <p class="text-xs text-slate-500 max-w-2xl">
                    Assign default Chairperson and Panel Members for all research groups in a class. Groups can inherit this assignment or have custom exceptions. Faculty advisers are eligible to serve as chairperson.
                </p>
            </div>
            <button
                type="button"
                @click="showClassCommitteeModal = false"
                class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors cursor-pointer"
            >
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 sm:p-8 space-y-6 max-h-[75vh] overflow-y-auto">
            <!-- Selector Row: Class & Defense Type -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50/80 p-4 rounded-2xl border border-slate-200/80">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Research Class <span class="text-rose-500">*</span>
                    </label>
                    <select
                        x-model="classCommitteeForm.classId"
                        @change="onClassCommitteeClassChange()"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none transition"
                    >
                        <template x-for="rc in facilitatorClasses" :key="rc.id">
                            <option :value="rc.id" x-text="rc.name + ' (' + rc.code + ')'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Defense Stage <span class="text-rose-500">*</span>
                    </label>
                    <select
                        x-model="classCommitteeForm.type"
                        @change="loadClassCommittees()"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none transition"
                    >
                        <option value="title_presentation">Title Presentation</option>
                        <option value="proposal_defense">Proposal Defense</option>
                        <option value="pre_final_defense">Pre-Final Defense</option>
                        <option value="final_defense">Final Defense</option>
                    </select>
                </div>
            </div>

            <!-- Two-Column Layout: Panel Form (Left) & Groups Coverage (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left: Panel Members Picker (5 cols) -->
                <div class="lg:col-span-5 space-y-5">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <i class="ph ph-user-circle-gear text-base text-[#0e5c3a]"></i>
                                Committee Evaluators
                            </h3>
                            <span class="text-[10px] font-bold text-slate-400">3 Members Required</span>
                        </div>

                        <!-- Chairperson -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                Chairperson <span class="text-rose-500">*</span>
                                <span class="text-[10px] text-emerald-700 font-normal ml-1">(Adviser eligible)</span>
                            </label>
                            <select
                                x-model="classCommitteeForm.chairpersonId"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none"
                            >
                                <option value="">-- Select Faculty Chairperson --</option>
                                <template x-for="c in candidateFacultyList" :key="'chair-' + c.id">
                                    <option :value="c.id" x-text="c.name + ' (' + (c.department || 'Faculty') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Panel Member 1 -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                Panel Member 1 <span class="text-rose-500">*</span>
                            </label>
                            <select
                                x-model="classCommitteeForm.panelMember1Id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none"
                            >
                                <option value="">-- Select First Panelist --</option>
                                <template x-for="c in candidateFacultyList" :key="'p1-' + c.id">
                                    <option :value="c.id" x-text="c.name + ' (' + (c.department || 'Faculty') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Panel Member 2 -->
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                Panel Member 2 <span class="text-rose-500">*</span>
                            </label>
                            <select
                                x-model="classCommitteeForm.panelMember2Id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-2xs focus:border-[#0e5c3a] focus:ring-1 focus:ring-[#0e5c3a] outline-none"
                            >
                                <option value="">-- Select Second Panelist --</option>
                                <template x-for="c in candidateFacultyList" :key="'p2-' + c.id">
                                    <option :value="c.id" x-text="c.name + ' (' + (c.department || 'Faculty') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Warning / Helper on distinct members -->
                        <div
                            x-show="classCommitteeForm.chairpersonId && (classCommitteeForm.chairpersonId === classCommitteeForm.panelMember1Id || classCommitteeForm.chairpersonId === classCommitteeForm.panelMember2Id || (classCommitteeForm.panelMember1Id && classCommitteeForm.panelMember1Id === classCommitteeForm.panelMember2Id))"
                            class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-800 flex items-center gap-2"
                        >
                            <i class="ph ph-warning text-base text-amber-600 shrink-0"></i>
                            <span>All 3 committee members must be distinct faculty members.</span>
                        </div>

                        <!-- Scope & Safety Options -->
                        <div class="pt-3 border-t border-slate-100 space-y-3">
                            <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                Target Groups
                            </label>
                            
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                    <input
                                        type="radio"
                                        value="all"
                                        x-model="classCommitteeForm.applyScope"
                                        class="text-[#0e5c3a] focus:ring-[#0e5c3a]"
                                    >
                                    <span>All groups in this class</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                    <input
                                        type="radio"
                                        value="selected"
                                        x-model="classCommitteeForm.applyScope"
                                        class="text-[#0e5c3a] focus:ring-[#0e5c3a]"
                                    >
                                    <span>Selected groups only (<span x-text="classCommitteeForm.selectedGroupIds.length"></span> chosen)</span>
                                </label>
                            </div>

                            <!-- Overwrite custom assignment safeguard -->
                            <div class="pt-2">
                                <label class="flex items-start gap-2 text-xs text-slate-700 cursor-pointer p-2 rounded-xl bg-slate-50 border border-slate-200/80">
                                    <input
                                        type="checkbox"
                                        x-model="classCommitteeForm.overwriteCustom"
                                        class="mt-0.5 rounded text-[#0e5c3a] focus:ring-[#0e5c3a]"
                                    >
                                    <div>
                                        <span class="font-bold text-slate-800">Overwrite custom group assignments</span>
                                        <p class="text-[10px] text-slate-500 leading-tight mt-0.5">
                                            If unchecked, groups with existing customized panels will keep their custom members.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Class Groups Status & Exceptions (7 cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800">
                                Research Groups (<span x-text="classCommitteeGroups.length"></span>)
                            </h3>
                            <p class="text-[11px] text-slate-500">Select which groups to apply or review panel status.</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs" x-show="classCommitteeForm.applyScope === 'selected'">
                            <button
                                type="button"
                                @click="selectAllCommitteeGroups()"
                                class="text-[11px] font-bold text-[#0e5c3a] hover:underline cursor-pointer"
                            >
                                Select All
                            </button>
                            <span class="text-slate-300">•</span>
                            <button
                                type="button"
                                @click="deselectAllCommitteeGroups()"
                                class="text-[11px] font-bold text-slate-500 hover:underline cursor-pointer"
                            >
                                Deselect All
                            </button>
                        </div>
                    </div>

                    <!-- Groups List -->
                    <div class="space-y-2.5 max-h-[480px] overflow-y-auto pr-1">
                        <template x-if="classCommitteeGroups.length === 0">
                            <div class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-xs text-slate-500">
                                No research groups found in this class.
                            </div>
                        </template>

                        <template x-for="grp in classCommitteeGroups" :key="grp.id">
                            <div
                                class="p-3.5 rounded-2xl border transition-all"
                                :class="{
                                    'border-[#0e5c3a]/40 bg-emerald-50/30': classCommitteeForm.selectedGroupIds.includes(grp.id),
                                    'border-slate-200 bg-white hover:border-slate-300': !classCommitteeForm.selectedGroupIds.includes(grp.id)
                                }"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <input
                                            type="checkbox"
                                            :value="grp.id"
                                            x-model="classCommitteeForm.selectedGroupIds"
                                            x-show="classCommitteeForm.applyScope === 'selected'"
                                            class="mt-1 rounded text-[#0e5c3a] focus:ring-[#0e5c3a] cursor-pointer"
                                        >
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-black text-slate-900" x-text="grp.group_name"></span>
                                                <!-- Status Badge -->
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider border"
                                                    :class="{
                                                        'bg-emerald-50 text-emerald-700 border-emerald-200': grp.committee_status === 'class',
                                                        'bg-purple-50 text-purple-700 border-purple-200': grp.committee_status === 'custom',
                                                        'bg-amber-50 text-amber-700 border-amber-200': grp.committee_status === 'missing'
                                                    }"
                                                    x-text="grp.committee_status_label"
                                                ></span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 truncate max-w-md" x-text="grp.title"></p>
                                            <p class="text-[10px] text-slate-400">
                                                Adviser: <span class="font-bold text-slate-700" x-text="grp.adviser_name || 'None Assigned'"></span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Action to customize for this single group -->
                                    <button
                                        type="button"
                                        @click="openSingleGroupCustomize(grp)"
                                        class="text-[11px] font-bold text-[#0e5c3a] hover:text-[#073823] px-2 py-1 rounded-lg hover:bg-emerald-50 transition-colors shrink-0 cursor-pointer"
                                        title="Assign specific exceptions for this group"
                                    >
                                        <i class="ph ph-pencil-simple"></i>
                                        <span>Customize</span>
                                    </button>
                                </div>

                                <!-- Active Committee Breakdown for this group -->
                                <div class="mt-2.5 pt-2 border-t border-slate-100 flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-slate-600">
                                    <div>
                                        <span class="text-slate-400 font-bold uppercase">Chair:</span>
                                        <span class="font-semibold text-slate-800" x-text="grp.committee?.chairperson_name || 'Not assigned'"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold uppercase">Panel 1:</span>
                                        <span class="font-semibold text-slate-800" x-text="grp.committee?.panel_member_1_name || 'Not assigned'"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 font-bold uppercase">Panel 2:</span>
                                        <span class="font-semibold text-slate-800" x-text="grp.committee?.panel_member_2_name || 'Not assigned'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/80 px-6 py-4 sm:px-8">
            <button
                type="button"
                @click="showClassCommitteeModal = false"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
            >
                Cancel
            </button>
            <button
                type="button"
                @click="submitClassCommittees()"
                :disabled="isSubmittingClassCommittee || !classCommitteeForm.chairpersonId || !classCommitteeForm.panelMember1Id || !classCommitteeForm.panelMember2Id"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] px-6 py-2.5 text-xs font-black text-white shadow-md hover:brightness-110 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
            >
                <i x-show="!isSubmittingClassCommittee" class="ph ph-check-circle text-base text-[#eebc3f]"></i>
                <i x-show="isSubmittingClassCommittee" class="ph ph-spinner animate-spin text-base"></i>
                <span x-text="isSubmittingClassCommittee ? 'Saving Assignments...' : 'Save & Propagate Assignments'"></span>
            </button>
        </div>
    </div>
</div>

<!-- Group-Level Customization Sub-Modal -->
<div
    x-show="showCustomGroupModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="showCustomGroupModal"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"
        @click="showCustomGroupModal = false"
    ></div>

    <div
        x-show="showCustomGroupModal"
        class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/10 z-10 space-y-4"
        @click.stop
    >
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-base font-black font-heading text-slate-900">Customize Panel Exception</h3>
                <p class="text-xs text-slate-500" x-text="customGroupData?.group_name"></p>
            </div>
            <button
                type="button"
                @click="showCustomGroupModal = false"
                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100"
            >
                <i class="ph ph-x"></i>
            </button>
        </div>

        <div class="space-y-3.5 text-xs">
            <div>
                <label class="block font-bold text-slate-700 mb-1">
                    Chairperson <span class="text-rose-500">*</span>
                    <span class="text-[10px] text-emerald-700 font-normal ml-1">(Adviser eligible)</span>
                </label>
                <select
                    x-model="customGroupForm.chairpersonId"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800"
                >
                    <option value="">-- Select Chairperson --</option>
                    <template x-for="c in candidateFacultyList" :key="'cg-chair-' + c.id">
                        <option :value="c.id" x-text="c.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Panel Member 1 <span class="text-rose-500">*</span></label>
                <select
                    x-model="customGroupForm.panelMember1Id"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800"
                >
                    <option value="">-- Select Panelist 1 --</option>
                    <template x-for="c in candidateFacultyList" :key="'cg-p1-' + c.id">
                        <option :value="c.id" x-text="c.name"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Panel Member 2 <span class="text-rose-500">*</span></label>
                <select
                    x-model="customGroupForm.panelMember2Id"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800"
                >
                    <option value="">-- Select Panelist 2 --</option>
                    <template x-for="c in candidateFacultyList" :key="'cg-p2-' + c.id">
                        <option :value="c.id" x-text="c.name"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-100">
            <button
                type="button"
                @click="resetGroupToClassCommittee()"
                class="text-xs font-bold text-slate-500 hover:text-rose-600 cursor-pointer"
            >
                Revert to Class Default
            </button>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="showCustomGroupModal = false"
                    class="rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="saveCustomGroupCommittee()"
                    class="rounded-xl bg-[#0e5c3a] px-4 py-1.5 text-xs font-bold text-white hover:bg-[#073823]"
                >
                    Save Custom Panel
                </button>
            </div>
        </div>
    </div>
</div>
