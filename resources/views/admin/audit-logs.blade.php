<div class="space-y-8 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col gap-1">
        <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400">
            <span>Admin Portal</span>
            <span>/</span>
            <span class="text-[#0e5c3a]">Security Audit Logs</span>
        </div>
        <h1 class="font-heading text-2xl md:text-3xl font-extrabold text-gray-900 tracking-tight">System Audit & Activity Logs</h1>
        <p class="text-xs text-gray-500 max-w-2xl">Monitor real-time system events, user administrative actions, workspace transitions, and security policy modifications.</p>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
        <!-- Card 1: Events Today -->
        <div class="group bg-white rounded-2xl p-5 shadow-xs hover:shadow-md border border-gray-200/80 hover:border-emerald-300 transition-all duration-200 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider block">Events Recorded Today</span>
                <span class="text-3xl font-black text-gray-900 font-heading tracking-tight block">{{ $auditLogStats['today'] }}</span>
                <span class="text-[10px] font-medium text-emerald-600 block">System execution log</span>
            </div>
            <div class="w-12 h-12 bg-emerald-50/80 group-hover:bg-emerald-100/80 rounded-2xl flex items-center justify-center text-[#0e5c3a] text-xl border border-emerald-100 transition-colors">
                <i class="ph ph-lightning font-bold"></i>
            </div>
        </div>

        <!-- Card 2: Workspace Switches -->
        <div class="group bg-white rounded-2xl p-5 shadow-xs hover:shadow-md border border-gray-200/80 hover:border-blue-300 transition-all duration-200 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider block">Workspace Switches</span>
                <span class="text-3xl font-black text-gray-900 font-heading tracking-tight block">{{ $auditLogStats['workspace_switches'] }}</span>
                <span class="text-[10px] font-medium text-blue-600 block">Context transitions</span>
            </div>
            <div class="w-12 h-12 bg-blue-50/80 group-hover:bg-blue-100/80 rounded-2xl flex items-center justify-center text-blue-600 text-xl border border-blue-100 transition-colors">
                <i class="ph ph-arrows-left-right font-bold"></i>
            </div>
        </div>

        <!-- Card 3: Access Changes -->
        <div class="group bg-white rounded-2xl p-5 shadow-xs hover:shadow-md border border-gray-200/80 hover:border-amber-300 transition-all duration-200 flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider block">Access & Role Changes</span>
                <span class="text-3xl font-black text-gray-900 font-heading tracking-tight block">{{ $auditLogStats['access_changes'] }}</span>
                <span class="text-[10px] font-medium text-amber-600 block">Permission modifications</span>
            </div>
            <div class="w-12 h-12 bg-amber-50/80 group-hover:bg-amber-100/80 rounded-2xl flex items-center justify-center text-amber-600 text-xl border border-amber-100 transition-colors">
                <i class="ph ph-shield-checkered font-bold"></i>
            </div>
        </div>
    </div>

    <!-- Filter Controls Card -->
    <section class="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-gray-700 flex items-center gap-2">
                <i class="ph ph-funnel text-base text-[#0e5c3a]"></i>
                <span>Filter & Search Audit Trail</span>
            </h2>
            <span class="text-[10px] font-semibold text-gray-400">Real-time filtering</span>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 items-end">
            <!-- Search Activity -->
            <label class="lg:col-span-4 space-y-1.5">
                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Search Query</span>
                <div class="relative block">
                    <i class="ph ph-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input 
                        type="search" 
                        wire:model.live.debounce.350ms="auditSearch" 
                        placeholder="Search actor, target, email, event, or IP..." 
                        class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-xs text-gray-800 placeholder-gray-400 focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                    >
                </div>
            </label>

            <!-- Event Type Dropdown -->
            <label class="lg:col-span-3 space-y-1.5">
                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">Event Type</span>
                <select 
                    wire:model.live="auditEvent" 
                    class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-gray-800 focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all cursor-pointer"
                >
                    <option value="">All Security & System Events</option>
                    @foreach ($auditLogEvents as $event)
                        <option value="{{ $event }}">{{ str($event)->replace('.', ' ')->headline() }}</option>
                    @endforeach
                </select>
            </label>

            <!-- From Date -->
            <label class="lg:col-span-2 space-y-1.5">
                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">From Date</span>
                <input 
                    type="date" 
                    wire:model.live="auditDateFrom" 
                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs text-gray-800 focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all cursor-pointer"
                >
            </label>

            <!-- To Date -->
            <label class="lg:col-span-2 space-y-1.5">
                <span class="block text-[10px] font-extrabold uppercase tracking-wider text-gray-500">To Date</span>
                <input 
                    type="date" 
                    wire:model.live="auditDateTo" 
                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs text-gray-800 focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all cursor-pointer"
                >
            </label>

            <!-- Clear Button -->
            <div class="lg:col-span-1">
                <button 
                    type="button" 
                    wire:click="clearAuditFilters" 
                    class="w-full py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-red-50 hover:border-red-200 text-gray-600 hover:text-red-700 text-xs font-bold transition-all duration-200 flex items-center justify-center gap-1 cursor-pointer"
                    title="Reset all filters"
                >
                    <i class="ph ph-arrow-counter-clockwise text-sm"></i>
                    <span>Clear</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Main Audit Log Table Section -->
    <section class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-xs">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
            <div>
                <h2 class="font-bold text-base text-gray-900 flex items-center gap-2">
                    <i class="ph ph-list-magnifying-glass text-[#0e5c3a]"></i>
                    <span>Audit Log Records</span>
                </h2>
                <p class="mt-0.5 text-xs text-gray-500">Complete immutable record of system operations and administrative changes.</p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3.5 py-1 text-xs font-extrabold text-[#0e5c3a] border border-emerald-100">
                {{ $auditLogs->total() }} Records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-xs border-collapse">
                <thead class="bg-gradient-to-r from-[#0e5c3a] to-[#0a4a2e] text-white text-[10px] font-black uppercase tracking-wider shadow-xs">
                    <tr>
                        <th class="px-6 py-4">Actor</th>
                        <th class="px-6 py-4">Event Type</th>
                        <th class="px-6 py-4">Target Entity</th>
                        <th class="px-6 py-4">Details & State Changes</th>
                        <th class="px-6 py-4">IP Address</th>
                        <th class="px-6 py-4">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($auditLogs as $auditLog)
                        @php
                            $actorName = $auditLog->actor?->name ?? $auditLog->actor_name ?? 'System Account';
                            $actorEmail = $auditLog->actor?->email ?? $auditLog->actor_email;
                            $subjectName = $auditLog->subject_name
                                ?? $auditLog->auditable?->name
                                ?? $auditLog->auditable?->display_name
                                ?? $auditLog->auditable?->system_name
                                ?? 'Target Record';
                            $subjectEmail = $auditLog->subject_email ?? $auditLog->auditable?->email;
                            $subjectType = $auditLog->auditable_type
                                ? str(class_basename($auditLog->auditable_type))->headline()
                                : 'System Object';
                            $fromWorkspace = data_get($auditLog->old_values, 'workspace');
                            $toWorkspace = data_get($auditLog->new_values, 'workspace');
                            $oldStatus = data_get($auditLog->old_values, 'status');
                            $newStatus = data_get($auditLog->new_values, 'status');
                            $oldRoles = data_get($auditLog->old_values, 'roles');
                            $newRoles = data_get($auditLog->new_values, 'roles');

                            $eventKey = (string) $auditLog->event;
                            $badgeStyle = match(true) {
                                str_contains($eventKey, 'created') || str_contains($eventKey, 'approved') => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                str_contains($eventKey, 'switch') || str_contains($eventKey, 'login') => 'bg-blue-50 text-blue-800 border-blue-200',
                                str_contains($eventKey, 'updated') || str_contains($eventKey, 'rescheduled') => 'bg-amber-50 text-amber-800 border-amber-200',
                                str_contains($eventKey, 'deleted') || str_contains($eventKey, 'rejected') || str_contains($eventKey, 'cancelled') => 'bg-red-50 text-red-800 border-red-200',
                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="align-top hover:bg-gray-50/60 transition-colors duration-150">
                            <!-- Actor -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a]/10 text-[#0e5c3a] font-bold flex items-center justify-center text-xs shrink-0 border border-[#0e5c3a]/20">
                                        {{ substr($actorName, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $actorName }}</p>
                                        @if ($actorEmail)
                                            <p class="mt-0.5 text-[10px] font-medium text-gray-400">{{ $actorEmail }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Event -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-wider border {{ $badgeStyle }}">
                                    {{ str($auditLog->event)->replace('.', ' ')->headline() }}
                                </span>
                            </td>

                            <!-- Target -->
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900">{{ $subjectName }}</p>
                                @if ($subjectEmail)
                                    <p class="mt-0.5 text-[10px] font-medium text-gray-500">{{ $subjectEmail }}</p>
                                @endif
                                <span class="mt-1 inline-block rounded-md bg-gray-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-gray-500">
                                    {{ $subjectType }} #{{ $auditLog->auditable_id ?? 'N/A' }}
                                </span>
                            </td>

                            <!-- Details -->
                            <td class="max-w-md px-6 py-4 leading-relaxed">
                                <p class="text-xs text-gray-800 font-medium">{{ $auditLog->description ?: 'No description recorded.' }}</p>
                                @if ($auditLog->event === 'workspace.switched')
                                    <div class="mt-1.5 inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-[#0e5c3a] border border-emerald-100">
                                        <span>{{ str($fromWorkspace ?: 'unknown')->headline() }}</span>
                                        <i class="ph ph-arrow-right text-xs"></i>
                                        <span>{{ str($toWorkspace ?: 'unknown')->headline() }}</span>
                                    </div>
                                @endif
                                @if ($oldStatus !== null || $newStatus !== null)
                                    <div class="mt-1.5 inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-800 border border-amber-100">
                                        <span>Status:</span>
                                        <span>{{ str($oldStatus ?: 'none')->headline() }}</span>
                                        <i class="ph ph-arrow-right text-xs"></i>
                                        <span>{{ str($newStatus ?: 'none')->headline() }}</span>
                                    </div>
                                @endif
                                @if (is_array($oldRoles) || is_array($newRoles))
                                    <p class="mt-1.5 text-[10px] font-medium text-gray-600 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                        <span class="font-bold text-gray-700">Roles:</span>
                                        {{ collect($oldRoles ?? [])->map(fn ($role) => str($role)->headline())->join(', ') ?: 'None' }}
                                        &rarr;
                                        {{ collect($newRoles ?? [])->map(fn ($role) => str($role)->headline())->join(', ') ?: 'None' }}
                                    </p>
                                @endif
                            </td>

                            <!-- IP Address -->
                            <td class="px-6 py-4">
                                <span class="rounded-md bg-slate-100 px-2.5 py-1 font-mono text-[10px] font-bold text-slate-700 border border-slate-200">
                                    {{ $auditLog->ip_address ?: '127.0.0.1' }}
                                </span>
                            </td>

                            <!-- Timestamp -->
                            <td class="whitespace-nowrap px-6 py-4">
                                <p class="font-bold text-gray-800">{{ $auditLog->created_at?->timezone(config('ndmu-rmas.timezone'))->format('M j, Y g:i A') }}</p>
                                <p class="mt-0.5 text-[10px] font-semibold text-gray-400">{{ $auditLog->created_at?->diffForHumans() }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-sm font-semibold text-gray-400">
                                <i class="ph ph-folder-open text-3xl block mb-2 text-gray-300"></i>
                                No audit log records found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($auditLogs->hasPages())
            <div class="border-t border-gray-100 px-6 py-4 bg-gray-50/50">
                {{ $auditLogs->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </section>
</div>
