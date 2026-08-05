<section x-show="activeTab === 'permissions'" x-cloak class="space-y-6 animate-fade-in">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold font-heading text-gray-800 tracking-tight">Roles &amp; Permissions</h1>
            <p class="mt-1 text-sm font-light text-gray-500">Build reusable access roles, then assign them to users from User Management.</p>
        </div>
        <button type="button" wire:click="resetRoleEditor" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#0a4a2e]">
            <i class="ph ph-plus-circle text-base"></i>
            Create Role
        </button>
    </div>

    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-xs text-blue-800">
        <strong>Safe access model:</strong> permissions are selected from the application catalog. Custom roles add access but do not replace the user&rsquo;s primary Student, Adviser, Panelist, Facilitator, Dean, or Administrator portal role.
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.5fr)]">
        <div class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-bold text-gray-900">Available Roles</h2>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold text-gray-600">{{ $rolesList->count() }}</span>
            </div>

            <div class="space-y-3">
                @foreach ($rolesList as $availableRole)
                    <article wire:key="role-{{ $availableRole['id'] }}" class="rounded-2xl border border-gray-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="truncate text-sm font-bold text-gray-900">{{ $availableRole['label'] }}</h3>
                                    @if ($availableRole['protected'])
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold uppercase text-slate-600">Built in</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-bold uppercase text-emerald-700">Custom</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-[11px] text-gray-500">
                                    {{ $availableRole['permissions_count'] }} permissions &middot; {{ $availableRole['users_count'] }} users
                                </p>
                            </div>

                            @unless ($availableRole['protected'])
                                <div class="flex shrink-0 gap-1">
                                    <button type="button" wire:click="editRole({{ $availableRole['id'] }})" class="rounded-lg p-2 text-blue-600 hover:bg-blue-50" aria-label="Edit {{ $availableRole['label'] }}">
                                        <i class="ph ph-pencil-simple"></i>
                                    </button>
                                    <button type="button" wire:click="deleteRole({{ $availableRole['id'] }})" wire:confirm="Delete this custom role? It must not be assigned to any user." class="rounded-lg p-2 text-red-600 hover:bg-red-50" aria-label="Delete {{ $availableRole['label'] }}">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </div>
                            @endunless
                        </div>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach (array_slice($availableRole['permissions'], 0, 4) as $permission)
                                <span class="rounded-lg bg-gray-50 px-2 py-1 text-[9px] font-semibold text-gray-600">{{ $permission }}</span>
                            @endforeach
                            @if ($availableRole['permissions_count'] > 4)
                                <span class="rounded-lg bg-gray-100 px-2 py-1 text-[9px] font-bold text-gray-500">+{{ $availableRole['permissions_count'] - 4 }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <form wire:submit="saveRole" class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="border-b border-gray-100 pb-5">
                <h2 class="font-bold text-gray-900">{{ $editingRoleId ? 'Edit Custom Role' : 'Create Custom Role' }}</h2>
                <p class="mt-1 text-xs text-gray-500">Use a clear role name and choose only the access it needs.</p>

                <label for="role-name" class="mt-5 block text-[11px] font-bold uppercase tracking-wide text-gray-600">Role name</label>
                <input id="role-name" wire:model="roleName" type="text" maxlength="80" placeholder="e.g. program-coordinator" autocomplete="off" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:ring-[#0e5c3a]">
                <p class="mt-1 text-[10px] text-gray-400">The name is stored as a lowercase slug, such as <code>program-coordinator</code>.</p>
                @error('roleName') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-5 space-y-5">
                @foreach ($permissionCatalog as $group => $permissions)
                    <fieldset>
                        <legend class="mb-2 text-[10px] font-extrabold uppercase tracking-wider text-[#0e5c3a]">{{ $group }}</legend>
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                            @foreach ($permissions as $permission => $details)
                                <label wire:key="permission-{{ $permission }}" class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-100 p-3 hover:border-emerald-200 hover:bg-emerald-50/40">
                                    <input wire:model="selectedPermissions" type="checkbox" value="{{ $permission }}" class="mt-1 rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                                    <span>
                                        <span class="block text-xs font-bold text-gray-800">{{ $details['label'] }}</span>
                                        <span class="mt-0.5 block text-[10px] leading-4 text-gray-500">{{ $details['description'] }}</span>
                                        <span class="mt-1 block text-[9px] font-semibold text-emerald-700">Scope: {{ $details['scope'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
                @error('selectedPermissions') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                @error('selectedPermissions.*') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-5">
                <button type="button" wire:click="resetRoleEditor" class="rounded-xl border border-gray-200 px-4 py-2.5 text-xs font-bold text-gray-600">Clear</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveRole" class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveRole">Save Role</span>
                    <span wire:loading wire:target="saveRole">Saving...</span>
                </button>
            </div>
        </form>
    </div>
</section>

@if ($roleAssignmentUser)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="role-assignment-title">
        <button type="button" wire:click="closeRoleAssignment" class="absolute inset-0 bg-slate-950/50" aria-label="Close role assignment"></button>
        <form wire:submit="saveUserRoles" class="relative z-10 w-full max-w-xl rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-gray-100 p-6">
                <div>
                    <h2 id="role-assignment-title" class="text-xl font-bold text-gray-900">Assign Roles</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ $roleAssignmentUser->name }} &middot; {{ $roleAssignmentUser->email }}</p>
                </div>
                <button type="button" wire:click="closeRoleAssignment" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"><i class="ph ph-x"></i></button>
            </div>

            <div class="max-h-[60vh] space-y-2 overflow-y-auto p-6">
                @foreach ($rolesList as $availableRole)
                    <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-gray-100 p-4 hover:bg-gray-50">
                        <span>
                            <span class="block text-sm font-bold text-gray-800">{{ $availableRole['label'] }}</span>
                            <span class="mt-1 block text-[10px] text-gray-500">{{ $availableRole['protected'] ? 'Primary portal role' : 'Custom access role' }} &middot; {{ $availableRole['permissions_count'] }} permissions</span>
                        </span>
                        <input wire:model="assignedRoles" type="checkbox" value="{{ $availableRole['name'] }}" class="rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                    </label>
                @endforeach
                @error('assignedRoles') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                @error('assignedRoles.*') <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 rounded-b-3xl border-t border-gray-100 bg-gray-50 p-5">
                <button type="button" wire:click="closeRoleAssignment" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-600">Cancel</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveUserRoles" class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white disabled:opacity-60">Save Assignments</button>
            </div>
        </form>
    </div>
@endif
