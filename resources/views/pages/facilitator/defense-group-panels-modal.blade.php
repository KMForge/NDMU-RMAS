<div x-show="showClassCommitteeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6" role="dialog" aria-modal="true" aria-labelledby="panel-assignment-title">
    <div class="mx-auto my-4 w-full max-w-7xl overflow-hidden rounded-3xl bg-slate-50 shadow-2xl ring-1 ring-black/10" @click.stop>
        <div class="h-2 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>
        <header class="flex flex-col gap-4 border-b border-slate-200 bg-white px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-7">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[.2em] text-emerald-700">Defense Management</p>
                <h2 id="panel-assignment-title" class="mt-1 text-xl font-black text-slate-950 sm:text-2xl">Panel Assignment by Research Group</h2>
                <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500">Every group has its own chairperson and panel selectors. Saving sends individual RES-028 invitations and tracks each response.</p>
            </div>
            <button type="button" @click="showClassCommitteeModal = false" class="self-end rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 sm:self-auto" aria-label="Close"><i class="ph ph-x text-xl"></i></button>
        </header>

        <div class="space-y-5 p-4 sm:p-7">
            <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2">
                <label class="text-[11px] font-black uppercase tracking-wider text-slate-600">Research Class
                    <select x-model="classCommitteeForm.classId" @change="onClassCommitteeClassChange()" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-bold normal-case text-slate-800 outline-none focus:border-emerald-600">
                        <template x-for="rc in facilitatorClasses" :key="rc.id"><option :value="rc.id" x-text="rc.name + ' (' + rc.code + ')' "></option></template>
                    </select>
                </label>
                <label class="text-[11px] font-black uppercase tracking-wider text-slate-600">Defense Stage
                    <select x-model="classCommitteeForm.type" @change="loadClassCommittees()" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs font-bold normal-case text-slate-800 outline-none focus:border-emerald-600">
                        <option value="title_presentation">Title Presentation</option><option value="proposal_defense">Proposal Defense</option><option value="pre_final_defense">Pre-Final Defense</option><option value="final_defense">Final Defense</option>
                    </select>
                </label>
            </div>

            <div x-show="classCommitteeForm.errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-700" x-text="classCommitteeForm.errorMessage"></div>
            <div x-show="classCommitteeForm.successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700" x-text="classCommitteeForm.successMessage"></div>
            <div x-show="classCommitteeForm.loading" class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm font-bold text-slate-500"><i class="ph ph-spinner-gap mr-2 animate-spin"></i>Loading research groups...</div>

            <div x-show="!classCommitteeForm.loading" class="grid gap-5 xl:grid-cols-2">
                <template x-if="classCommitteeGroups.length === 0"><div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center text-sm text-slate-500">No active research groups were found in this class.</div></template>
                <template x-for="group in classCommitteeGroups" :key="group.id">
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50/80 to-white p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="truncate text-base font-black text-slate-950" x-text="group.group_name || group.name"></h3><span class="rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider" :class="group.is_complete ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'" x-text="group.is_complete ? 'Panel selected' : 'Needs assignment'"></span></div><p class="mt-1 line-clamp-2 text-[11px] leading-4 text-slate-500" x-text="group.title || 'No research title registered yet'"></p><p class="mt-1 text-[10px] text-slate-400">Adviser: <span class="font-bold text-slate-600" x-text="group.adviser_name || 'Not assigned'"></span></p></div>
                                <span class="shrink-0 rounded-lg bg-slate-100 px-2 py-1 text-[9px] font-black uppercase text-slate-500" x-text="group.res033_complete ? 'Ready' : 'RES-033 pending'"></span>
                            </div>
                        </div>
                        <div class="space-y-3 p-4 sm:p-5">
                            <template x-for="slot in [{key:'chairperson',label:'Chairperson',model:'editChairpersonId',status:'chairperson_invitation_status'},{key:'member_1',label:'Panel Member 1',model:'editMember1Id',status:'member_1_invitation_status'},{key:'member_2',label:'Panel Member 2',model:'editMember2Id',status:'member_2_invitation_status'}]" :key="slot.key">
                                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                    <div class="mb-2 flex items-center justify-between gap-2"><label class="text-[10px] font-black uppercase tracking-wider text-slate-600" x-text="slot.label"></label><span x-show="group[slot.status]" class="rounded-full border px-2 py-0.5 text-[9px] font-black uppercase" :class="{'border-amber-200 bg-amber-50 text-amber-700':group[slot.status]==='pending','border-emerald-200 bg-emerald-50 text-emerald-700':group[slot.status]==='accepted','border-rose-200 bg-rose-50 text-rose-700':group[slot.status]==='rejected'}" x-text="group[slot.status]"></span></div>
                                    <select x-model="group[slot.model]" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-800 outline-none focus:border-emerald-600"><option value="" x-text="'Select ' + slot.label"></option><template x-for="candidate in candidateFacultyList" :key="slot.key+'-'+candidate.id"><option :value="String(candidate.id)" x-text="candidate.name + ' (' + (candidate.department || 'Faculty') + ')' "></option></template></select>
                                </div>
                            </template>
                            <p x-show="group.saveError" class="rounded-lg bg-rose-50 px-3 py-2 text-[11px] font-bold text-rose-700" x-text="group.saveError"></p>
                            <div class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-[10px] leading-4 text-slate-400">Changing a person cancels the earlier pending invitation and sends a new RES-028.</p><button type="button" @click="saveInlineGroupCommittee(group)" :disabled="group.saving" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-black text-white hover:bg-[#073823] disabled:opacity-60"><i class="ph" :class="group.saving ? 'ph-spinner-gap animate-spin' : 'ph-paper-plane-tilt'"></i><span x-text="group.saving ? 'Saving...' : 'Save & Send Invitations'"></span></button></div>
                        </div>
                    </article>
                </template>
            </div>
        </div>
    </div>
</div>
