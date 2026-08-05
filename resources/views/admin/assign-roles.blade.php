<section x-show="activeTab === 'assign-roles'" x-cloak class="space-y-6 animate-fade-in">
    @if ($roleAssignmentUser)
        <div class="overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-sm">
            <div class="relative overflow-hidden bg-gradient-to-br from-[#0e5c3a] to-[#08452b] px-6 py-7 text-white md:px-8">
                <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full border-[35px] border-white/5"></div>
                <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-[#f4c542]">NDMU-RMAS Administration</p>
                        <h1 class="mt-2 font-heading text-3xl font-extrabold tracking-tight">Assign Roles</h1>
                        <p class="mt-2 max-w-2xl text-sm text-emerald-50/85">Assign one or more reusable roles to control this user’s permissions and available dashboards.</p>
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-white/80">
                            <span class="font-bold text-white">{{ $roleAssignmentUser->name }}</span>
                            <span aria-hidden="true">•</span>
                            <span>{{ $roleAssignmentUser->email }}</span>
                            <span class="rounded-full bg-white/10 px-2.5 py-1 font-bold uppercase tracking-wider text-white">{{ \Illuminate\Support\Str::headline($assignedUserType) }}</span>
                        </div>
                    </div>
                    <button type="button" wire:click="closeRoleAssignment" wire:loading.attr="disabled" wire:target="closeRoleAssignment" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 py-3 text-xs font-extrabold text-white transition hover:bg-white/20 disabled:opacity-60">
                        <i class="ph ph-arrow-left"></i>
                        Back to Users
                    </button>
                </div>
            </div>

            <div class="flex gap-3 border-t border-amber-200 bg-amber-50/80 px-6 py-4 text-xs text-amber-950 md:px-8">
                <i class="ph ph-lightbulb mt-0.5 text-lg text-amber-600"></i>
                <p><strong>Use least privilege:</strong> assign only the responsibilities this user currently needs. Changes apply immediately after saving.</p>
            </div>
        </div>

        <form wire:submit="saveUserRoles" class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-gray-100 bg-gray-50/50 p-5 md:flex-row md:items-center md:justify-between md:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#0e5c3a] font-extrabold text-white">{{ count($assignedRoles) }}</span>
                    <div>
                        <h2 class="text-base font-extrabold text-gray-900">Role Assignments</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Select every role this user should retain.</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" wire:click="selectAllAssignableRoles" wire:loading.attr="disabled" wire:target="selectAllAssignableRoles" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 transition hover:border-[#0e5c3a]/30 hover:text-[#0e5c3a] disabled:opacity-60">Select All</button>
                    <button type="button" wire:click="clearAssignedRoles" wire:loading.attr="disabled" wire:target="clearAssignedRoles" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 transition hover:border-red-200 hover:text-red-600 disabled:opacity-60">Clear</button>
                </div>
            </div>

            <div class="p-5 md:p-6">
                @error('assignedRoles')
                    <div class="mb-5 flex items-center gap-2 rounded-2xl border border-red-200 bg-red-50 p-4 text-xs font-semibold text-red-700">
                        <i class="ph ph-warning-circle text-lg"></i>{{ $message }}
                    </div>
                @enderror

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @foreach ($rolesList as $availableRole)
                        <label wire:key="assignable-role-{{ $availableRole['id'] }}" class="group flex cursor-pointer items-start gap-4 rounded-2xl border border-gray-200 p-4 transition hover:border-[#0e5c3a]/30 hover:bg-emerald-50/30 has-[:checked]:border-[#0e5c3a]/40 has-[:checked]:bg-emerald-50/60">
                            <input type="checkbox" value="{{ $availableRole['name'] }}" wire:model.live="assignedRoles" class="mt-1 h-5 w-5 shrink-0 rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-extrabold text-gray-900">{{ $availableRole['label'] }}</span>
                                    @if ($availableRole['protected'])
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-slate-600">Protected</span>
                                    @endif
                                </span>
                                <span class="mt-1 block text-xs leading-5 text-gray-500">{{ $availableRole['description'] ?: 'Access is controlled by the permissions attached to this role.' }}</span>
                                <span class="mt-2 block text-[10px] font-bold uppercase tracking-wider text-[#0e5c3a]">{{ $availableRole['permissions_count'] }} {{ \Illuminate\Support\Str::plural('permission', $availableRole['permissions_count']) }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50/50 px-5 py-5 sm:flex-row sm:items-center sm:justify-between md:px-6">
                <p class="text-[11px] text-gray-500">Role changes are audited and take effect immediately after saving.</p>
                <div class="flex gap-3">
                    <button type="button" wire:click="closeRoleAssignment" wire:loading.attr="disabled" class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-xs font-bold text-gray-700 transition hover:bg-gray-50 disabled:opacity-60">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveUserRoles" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-5 py-3 text-xs font-extrabold text-white shadow-md transition hover:bg-[#0a4a2e] disabled:cursor-wait disabled:opacity-60">
                        <i class="ph ph-floppy-disk"></i>
                        <span wire:loading.remove wire:target="saveUserRoles">Save Roles</span>
                        <span wire:loading wire:target="saveUserRoles">Saving…</span>
                    </button>
                </div>
            </div>
        </form>
    @endif
</section>
