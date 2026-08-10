<section
    x-show="activeTab === 'permissions'"
    x-cloak
    x-data="{ roleSearch: '' }"
    class="space-y-6 animate-fade-in"
>
    @if (! $showRoleEditor)
        <div class="overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-sm">
            <div class="relative overflow-hidden bg-gradient-to-br from-[#0e5c3a] to-[#08452b] px-6 py-7 text-white md:px-8">
                <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full border-[35px] border-white/5"></div>
                <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-[#f4c542]">NDMU-RMAS Administration</p>
                        <h1 class="mt-2 font-heading text-3xl font-extrabold tracking-tight">Roles &amp; Permissions</h1>
                        <p class="mt-2 max-w-2xl text-sm text-emerald-50/85">Create reusable roles, attach only the permissions they need, then assign one or more roles to users.</p>
                    </div>
                    <button type="button" wire:click="createRole" wire:loading.attr="disabled" wire:target="createRole" @click="activeTab = 'permissions'" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[#f4c542] px-5 py-3 text-xs font-extrabold text-[#0e5c3a] shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:bg-[#ffd65e] disabled:cursor-wait disabled:opacity-60">
                        <i class="ph ph-plus-circle text-lg"></i>
                        Add Role
                    </button>
                </div>
            </div>

            <div class="grid divide-y divide-gray-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                <div class="px-6 py-5 md:px-8">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total roles</p>
                    <div class="mt-2 flex items-end gap-2">
                        <strong class="text-3xl font-extrabold text-gray-900">{{ $rolesList->count() }}</strong>
                        <span class="pb-1 text-xs text-gray-500">available for assignment</span>
                    </div>
                </div>
                <div class="px-6 py-5 md:px-8">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Access model</p>
                    <p class="mt-2 text-sm font-bold text-[#0e5c3a]">Permission-based access control</p>
                    <p class="mt-1 text-xs text-gray-500">Permissions belong to roles; users may receive multiple roles.</p>
                </div>
            </div>
        </div>

        <div class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-xs text-amber-900">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"><i class="ph ph-lightbulb text-lg"></i></span>
            <div>
                <p class="font-extrabold">How roles work</p>
                <p class="mt-1 leading-5 text-amber-900/75">A role is a named bundle of permissions. User type only classifies an account as Student, Faculty, or Admin; roles determine what that account can actually see and do.</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-gray-100 p-5 md:flex-row md:items-center md:justify-between md:px-6">
                <div>
                    <h2 class="text-lg font-extrabold text-gray-900">Role Directory</h2>
                    <p class="mt-1 text-xs text-gray-500">Review roles, their assigned permissions, and current usage.</p>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <label class="relative block">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="ph ph-magnifying-glass"></i></span>
                        <input x-model="roleSearch" type="search" placeholder="Search roles..." class="w-full rounded-xl border-gray-200 py-2.5 pl-9 pr-3 text-xs focus:border-[#0e5c3a] focus:ring-[#0e5c3a] sm:w-60">
                    </label>
                    <button type="button" wire:click="createRole" wire:loading.attr="disabled" wire:target="createRole" @click="activeTab = 'permissions'" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#0a4a2e] disabled:cursor-wait disabled:opacity-60">
                        <i class="ph ph-plus-circle text-base"></i>
                        Add Role
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left">
                    <thead class="bg-gradient-to-r from-[#0e5c3a] to-[#0a4a2e] text-white text-[10px] font-black uppercase tracking-wider shadow-xs">
                        <tr>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Permissions</th>
                            <th class="px-6 py-4 text-center">Users</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($rolesList as $availableRole)
                            <tr
                                wire:key="role-directory-{{ $availableRole['id'] }}"
                                x-show="roleSearch === '' || @js(strtolower($availableRole['label'].' '.$availableRole['name'])).includes(roleSearch.toLowerCase())"
                                class="transition hover:bg-emerald-50/20"
                            >
                                <td class="px-6 py-5 align-top">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#0e5c3a] text-sm font-extrabold text-white">{{ mb_strtoupper(mb_substr($availableRole['label'], 0, 1)) }}</span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-bold text-gray-900">{{ $availableRole['label'] }}</p>
                                                @if ($availableRole['protected'])
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold uppercase text-slate-600">Protected</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 max-w-xs text-[11px] leading-4 text-gray-500">{{ $availableRole['description'] ?: 'Custom access role configured by an administrator.' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div class="flex max-w-2xl flex-wrap gap-1.5">
                                        @forelse (array_slice($availableRole['permissions'], 0, 5) as $permission)
                                            <span class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-[9px] font-semibold text-gray-600">{{ $permission }}</span>
                                        @empty
                                            <span class="text-[11px] italic text-gray-400">No permissions assigned</span>
                                        @endforelse
                                        @if ($availableRole['permissions_count'] > 5)
                                            <span class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-[9px] font-bold text-amber-700">+{{ $availableRole['permissions_count'] - 5 }} more</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-[10px] text-gray-400">{{ $availableRole['permissions_count'] }} {{ \Illuminate\Support\Str::plural('permission', $availableRole['permissions_count']) }}</p>
                                </td>
                                <td class="px-6 py-5 text-center align-top">
                                    <span class="inline-flex min-w-9 justify-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-extrabold text-emerald-700">{{ $availableRole['users_count'] }}</span>
                                </td>
                                <td class="px-6 py-5 align-top">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="editRole({{ $availableRole['id'] }})" wire:loading.attr="disabled" wire:target="editRole({{ $availableRole['id'] }})" @click="activeTab = 'permissions'" class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-[11px] font-bold text-blue-700 hover:bg-blue-100 disabled:cursor-wait disabled:opacity-60">Edit</button>
                                        @unless ($availableRole['protected'])
                                            <button type="button" wire:click="deleteRole({{ $availableRole['id'] }})" wire:confirm="Delete this role? It must not be assigned to any user." class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-[11px] font-bold text-red-600 hover:bg-red-100">Delete</button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-16 text-center text-sm text-gray-400">No roles are available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <form wire:submit="saveRole" class="space-y-6">
            <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-gray-100 bg-gradient-to-r from-[#0e5c3a] to-[#0a4a2e] p-6 text-white md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-[#f4c542]">Access Control</p>
                        <h1 class="mt-2 font-heading text-2xl font-extrabold">{{ $editingRoleId ? 'Edit Role' : 'Add Role' }}</h1>
                        <p class="mt-1 text-xs text-emerald-50/80">Enter a role name, then select the exact permissions it requires.</p>
                    </div>
                    <button type="button" wire:click="resetRoleEditor" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-bold text-white hover:bg-white/20">
                        <i class="ph ph-arrow-left"></i> Back to Roles
                    </button>
                </div>

                <div class="p-6 md:p-8">
                    <label for="role-name" class="block text-[11px] font-extrabold uppercase tracking-wide text-gray-600">Role name <span class="text-red-500">*</span></label>
                    <input id="role-name" wire:model="roleName" type="text" maxlength="80" placeholder="e.g. Capstone Coordinator" autocomplete="off" class="mt-2 w-full rounded-xl border-gray-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:ring-[#0e5c3a]">
                    <p class="mt-2 text-[10px] text-gray-400">Use a clear responsibility name. It will be stored securely as a lowercase slug.</p>
                    @error('roleName') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
                <div class="flex flex-col gap-3 border-b border-gray-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-900">Permissions</h2>
                        <p class="mt-1 text-xs text-gray-500">Choose only what this role genuinely needs.</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="selectAllPermissions" class="rounded-xl border border-gray-200 px-3 py-2 text-[11px] font-bold text-gray-600 hover:bg-gray-50">Select all</button>
                        <button type="button" wire:click="clearSelectedPermissions" class="rounded-xl border border-gray-200 px-3 py-2 text-[11px] font-bold text-gray-600 hover:bg-gray-50">Clear</button>
                    </div>
                </div>

                <div class="mt-6 space-y-7">
                    @foreach ($permissionCatalog as $group => $permissions)
                        <fieldset>
                            <legend class="mb-3 flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-wider text-[#0e5c3a]">
                                <span class="h-2 w-2 rounded-full bg-[#f4c542]"></span>{{ $group }}
                            </legend>
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                @foreach ($permissions as $permission => $details)
                                    <label wire:key="permission-editor-{{ $permission }}" class="group flex cursor-pointer items-start gap-3 rounded-2xl border border-gray-200 p-4 transition hover:border-emerald-300 hover:bg-emerald-50/30">
                                        <input wire:model="selectedPermissions" type="checkbox" value="{{ $permission }}" class="mt-1 rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                                        <span class="min-w-0">
                                            <span class="block text-sm font-bold text-gray-800">{{ $details['label'] }} <span class="font-mono text-[9px] font-normal text-gray-400">({{ $permission }})</span></span>
                                            <span class="mt-1 block text-[11px] leading-4 text-gray-500">{{ $details['description'] }}</span>
                                            <span class="mt-2 block text-[10px] font-semibold text-emerald-700">Scope: {{ $details['scope'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                    @error('selectedPermissions') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    @error('selectedPermissions.*') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-6 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="resetRoleEditor" class="rounded-xl border border-gray-200 px-5 py-3 text-xs font-bold text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveRole" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-6 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-900/10 hover:bg-[#0a4a2e] disabled:opacity-60">
                        <i class="ph ph-floppy-disk text-base" wire:loading.remove wire:target="saveRole"></i>
                        <span wire:loading.remove wire:target="saveRole">{{ $editingRoleId ? 'Update Role' : 'Create Role' }}</span>
                        <span wire:loading wire:target="saveRole">Saving role...</span>
                    </button>
                </div>
            </div>
        </form>
    @endif
</section>
