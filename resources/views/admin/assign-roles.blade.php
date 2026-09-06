<section x-show="activeTab === 'assign-roles'" x-cloak class="space-y-6 animate-fade-in">
    @if ($roleAssignmentUser)
        @php
            $addedRoles = array_values(array_diff($assignedRoles, $originalAssignedRoles));
            $removedRoles = array_values(array_diff($originalAssignedRoles, $assignedRoles));
            $hasActiveAdviserDuties = ($userActiveDuties['advised_groups_count'] ?? 0) > 0;
            $hasActivePanelDuties = ($userActiveDuties['panel_evaluations_count'] ?? 0) > 0;
            $hasPendingForms = ($userActiveDuties['pending_forms_count'] ?? 0) > 0;
            $removingCriticalRole = in_array('thesis-adviser', $removedRoles, true) && $hasActiveAdviserDuties
                || in_array('panel-member', $removedRoles, true) && $hasActivePanelDuties;
        @endphp

        <!-- Header Card -->
        <div class="overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-sm">
            <div class="relative overflow-hidden bg-gradient-to-br from-[#0e5c3a] to-[#08452b] px-6 py-7 text-white md:px-8">
                <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full border-[35px] border-white/5"></div>
                <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-400/20 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-[#f4c542] border border-[#f4c542]/30">
                                <i class="ph ph-shield-check text-xs"></i> Access Control & Role Management
                            </span>
                        </div>
                        <h1 class="mt-2 font-heading text-2xl sm:text-3xl font-extrabold tracking-tight">
                            @if ($roleAssignmentMode === 'assign')
                                Assign Roles to {{ $roleAssignmentUser->name }}
                            @else
                                Edit Roles for {{ $roleAssignmentUser->name }}
                            @endif
                        </h1>
                        <p class="mt-1 max-w-2xl text-xs sm:text-sm text-emerald-50/85">
                            @if ($roleAssignmentMode === 'assign')
                                Select roles below to grant this user new academic or administrative responsibilities.
                            @else
                                Review, add, or unselect existing roles to adjust permissions and workflow capabilities.
                            @endif
                        </p>
                        
                        <div class="mt-3.5 flex flex-wrap items-center gap-2.5 text-xs text-white/90">
                            <span class="font-mono text-emerald-200 bg-black/20 px-2.5 py-0.5 rounded-lg border border-white/10">{{ $roleAssignmentUser->email }}</span>
                            <span class="rounded-lg bg-white/15 px-2.5 py-0.5 font-bold uppercase tracking-wider text-white">{{ \Illuminate\Support\Str::headline($assignedUserType) }}</span>
                            @if ($roleAssignmentUser->department)
                                <span class="rounded-lg bg-[#f4c542]/20 text-[#f4c542] px-2.5 py-0.5 font-bold border border-[#f4c542]/30">{{ $roleAssignmentUser->department }}</span>
                            @endif
                        </div>
                    </div>
                    <button 
                        type="button" 
                        wire:click="closeRoleAssignment" 
                        wire:loading.attr="disabled" 
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-2.5 text-xs font-extrabold text-white transition hover:bg-white/20 disabled:opacity-60 cursor-pointer"
                    >
                        <i class="ph ph-arrow-left"></i>
                        Cancel & Return
                    </button>
                </div>
            </div>

            <!-- Active Responsibility Safeguard Notification -->
            @if ($hasActiveAdviserDuties || $hasActivePanelDuties || $hasPendingForms)
                <div class="border-t border-amber-200 bg-amber-50/90 px-6 py-3.5 text-xs text-amber-950 md:px-8 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-2.5">
                        <i class="ph ph-shield-warning text-lg text-amber-600 shrink-0 mt-0.5"></i>
                        <div>
                            <span class="font-black text-amber-900">Active Responsibility Audit:</span>
                            <span class="text-amber-800">
                                This faculty member is currently assigned to:
                                @if ($hasActiveAdviserDuties)
                                    <strong class="font-bold underline">{{ $userActiveDuties['advised_groups_count'] }} active research group(s) as Adviser</strong>@if($hasActivePanelDuties || $hasPendingForms), @endif
                                @endif
                                @if ($hasActivePanelDuties)
                                    <strong class="font-bold underline">{{ $userActiveDuties['panel_evaluations_count'] }} defense evaluation(s) as Panelist</strong>@if($hasPendingForms), @endif
                                @endif
                                @if ($hasPendingForms)
                                    <strong class="font-bold underline">{{ $userActiveDuties['pending_forms_count'] }} pending official signoff(s)</strong>
                                @endif.
                            </span>
                        </div>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-lg bg-amber-200/70 text-amber-900 text-[10px] font-black uppercase tracking-wider">
                        Protected Workflow
                    </span>
                </div>
            @endif
        </div>

        <form wire:submit="saveUserRoles" class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm space-y-0">
            
            <!-- Real-Time Pending Changes Delta Card -->
            <div class="border-b border-gray-100 bg-slate-50/90 p-5 md:px-8 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                            <i class="ph ph-git-diff text-[#0e5c3a]"></i>
                            Pending Role Adjustments
                        </h2>
                        <p class="text-[11px] text-slate-500 font-medium">Review the role changes below before applying them to the user account.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="selectAllAssignableRoles" wire:loading.attr="disabled" class="rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 transition hover:border-[#0e5c3a]/30 hover:text-[#0e5c3a] disabled:opacity-60 cursor-pointer">
                            Select All
                        </button>
                        <button type="button" wire:click="clearAssignedRoles" wire:loading.attr="disabled" class="rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 transition hover:border-red-200 hover:text-red-600 disabled:opacity-60 cursor-pointer">
                            Clear All
                        </button>
                    </div>
                </div>

                <!-- Delta Diff Badges -->
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    @if (empty($addedRoles) && empty($removedRoles))
                        <span class="text-xs text-slate-400 italic">No pending changes. Select or unselect roles below to make adjustments.</span>
                    @else
                        @foreach ($addedRoles as $addR)
                            @php $rLabel = collect($rolesList)->firstWhere('name', $addR)['label'] ?? str($addR)->replace('-', ' ')->title(); @endphp
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-2xs">
                                <i class="ph ph-plus-circle-fill text-emerald-700"></i>
                                <span>+ Add: {{ $rLabel }}</span>
                            </span>
                        @endforeach

                        @foreach ($removedRoles as $remR)
                            @php $rLabel = collect($rolesList)->firstWhere('name', $remR)['label'] ?? str($remR)->replace('-', ' ')->title(); @endphp
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-rose-100 text-rose-900 border border-rose-300 shadow-2xs">
                                <i class="ph ph-minus-circle-fill text-rose-700"></i>
                                <span>- Remove: {{ $rLabel }}</span>
                            </span>
                        @endforeach
                    @endif
                </div>

                <!-- Critical Role Warning -->
                @if ($removingCriticalRole)
                    <div class="rounded-xl border border-rose-300 bg-rose-50 p-3 text-xs text-rose-800 flex items-center gap-2 font-medium">
                        <i class="ph ph-warning-octagon text-lg text-rose-600 shrink-0"></i>
                        <span><strong>Safety Precaution:</strong> You are removing a role with ongoing active research/defense assignments. Please ensure replacement faculty are assigned to prevent workflow disruption.</span>
                    </div>
                @endif
            </div>

            <!-- Role Checkbox Grid with Clear Descriptions -->
            <div class="p-5 md:p-8">
                @error('assignedRoles')
                    <div class="mb-5 flex items-center gap-2 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-semibold text-red-700">
                        <i class="ph ph-warning-circle text-lg"></i>{{ $message }}
                    </div>
                @enderror

                <div class="grid grid-cols-1 gap-3.5 lg:grid-cols-2">
                    @foreach ($rolesList as $availableRole)
                        @php
                            $isRoleChecked = in_array($availableRole['name'], $assignedRoles, true);
                            $wasOriginallyChecked = in_array($availableRole['name'], $originalAssignedRoles, true);
                        @endphp
                        <label 
                            wire:key="assignable-role-{{ $availableRole['id'] }}" 
                            class="group flex cursor-pointer items-start gap-4 rounded-2xl border p-4.5 transition-all {{ $isRoleChecked ? 'border-emerald-500 bg-emerald-50/60 shadow-sm ring-1 ring-emerald-500/30' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-slate-50/50' }}"
                        >
                            <input 
                                type="checkbox" 
                                value="{{ $availableRole['name'] }}" 
                                wire:model.live="assignedRoles" 
                                class="mt-1 h-5 w-5 shrink-0 rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a] cursor-pointer"
                            >
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-sm font-black text-gray-900 group-hover:text-[#0e5c3a] transition-colors">
                                        {{ $availableRole['label'] }}
                                    </span>
                                    @if ($availableRole['protected'])
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-slate-600">Protected</span>
                                    @endif
                                </span>
                                <span class="mt-1 block text-xs leading-relaxed text-gray-500">{{ $availableRole['description'] ?: 'Access is controlled by the permissions attached to this role.' }}</span>
                                
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#0e5c3a]">
                                        {{ $availableRole['permissions_count'] }} {{ \Illuminate\Support\Str::plural('permission', $availableRole['permissions_count']) }}
                                    </span>

                                    @if ($isRoleChecked && !$wasOriginallyChecked)
                                        <span class="text-[10px] font-black text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md flex items-center gap-1">
                                            <i class="ph ph-plus-bold text-xs"></i> Will Be Added
                                        </span>
                                    @elseif (!$isRoleChecked && $wasOriginallyChecked)
                                        <span class="text-[10px] font-black text-rose-700 bg-rose-100 px-2 py-0.5 rounded-md flex items-center gap-1">
                                            <i class="ph ph-minus-bold text-xs"></i> Will Be Removed
                                        </span>
                                    @elseif ($isRoleChecked)
                                        <span class="text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                                            Currently Assigned
                                        </span>
                                    @endif
                                </div>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Controlled Save Bar with Explicit Confirmation -->
            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50/80 px-5 py-5 sm:flex-row sm:items-center sm:justify-between md:px-8">
                <p class="text-xs text-gray-500 flex items-center gap-1.5">
                    <i class="ph ph-lock-key text-slate-400"></i>
                    All changes take effect immediately upon submission and are recorded in the audit trail.
                </p>
                <div class="flex items-center gap-3">
                    <button 
                        type="button" 
                        wire:click="closeRoleAssignment" 
                        wire:loading.attr="disabled" 
                        class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-xs font-bold text-gray-700 transition hover:bg-gray-50 disabled:opacity-60 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        wire:loading.attr="disabled" 
                        wire:target="saveUserRoles" 
                        wire:confirm="Are you sure you want to apply these role changes for {{ $roleAssignmentUser->name }}?"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] px-6 py-2.5 text-xs font-black text-white shadow-md transition hover:brightness-110 disabled:cursor-wait disabled:opacity-60 cursor-pointer"
                    >
                        <i class="ph ph-floppy-disk"></i>
                        <span wire:loading.remove wire:target="saveUserRoles">Confirm & Save Roles</span>
                        <span wire:loading wire:target="saveUserRoles">Saving…</span>
                    </button>
                </div>
            </div>
        </form>
    @endif
</section>
