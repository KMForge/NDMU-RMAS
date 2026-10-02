<div class="space-y-7">
    <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#286b49] px-7 py-7 text-white shadow-xl shadow-emerald-950/15">
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-[#eebc3f]/15 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#f6cf69]">Protected administration</p>
                <h1 class="mt-2 font-heading text-3xl font-black">Backup Management</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-emerald-50/85">Create recoverable PostgreSQL and private-file archives, download protected copies, and control the automatic backup schedule.</p>
            </div>
            <button type="button" wire:click="runSystemBackup" wire:loading.attr="disabled" wire:target="runSystemBackup" class="inline-flex min-w-44 items-center justify-center gap-2 rounded-2xl bg-[#eebc3f] px-5 py-3 text-sm font-black text-[#073823] shadow-lg transition hover:bg-[#ffd66b] disabled:cursor-wait disabled:opacity-60">
                <i class="ph ph-play-circle text-lg" wire:loading.remove wire:target="runSystemBackup"></i>
                <i class="ph ph-spinner-gap animate-spin text-lg" wire:loading wire:target="runSystemBackup"></i>
                <span wire:loading.remove wire:target="runSystemBackup">Run Backup Now</span>
                <span wire:loading wire:target="runSystemBackup">Creating Backup…</span>
            </button>
        </div>
    </section>

    @error('backup')
        <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            <i class="ph ph-warning-circle mt-0.5 text-xl"></i>
            <div><p class="font-bold">Backup operation failed</p><p class="mt-1">{{ $message }}</p></div>
        </div>
    @enderror

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr_.8fr]">
        <form wire:submit="saveBackupSchedule" class="rounded-[2rem] border border-gray-100 bg-white p-7 shadow-sm">
            <div class="mb-6 flex items-start justify-between gap-5">
                <div>
                    <h2 class="font-heading text-lg font-extrabold text-gray-800">Automatic Backup Schedule</h2>
                    <p class="mt-1 text-xs leading-5 text-gray-500">The Laravel scheduler must be running on the server for automatic backups.</p>
                </div>
                <label class="relative mt-1 inline-flex shrink-0 cursor-pointer items-center">
                    <input type="checkbox" wire:model="backupScheduleEnabled" class="peer sr-only" aria-label="Enable automatic backups">
                    <span class="h-7 w-12 rounded-full bg-gray-300 transition peer-checked:bg-[#0e5c3a] peer-focus-visible:ring-4 peer-focus-visible:ring-[#0e5c3a]/20"></span>
                    <span class="pointer-events-none absolute left-1 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                </label>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label for="backup-frequency" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">Frequency</label>
                    <select id="backup-frequency" wire:model="backupFrequency" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                    @error('backupFrequency') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="backup-run-time" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">Run Time</label>
                    <input id="backup-run-time" type="time" wire:model="backupRunTime" class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5">
                    @error('backupRunTime') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="backup-retention" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">Keep Latest</label>
                    <input id="backup-retention" type="number" min="1" max="365" wire:model="backupRetentionCount" class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5">
                    @error('backupRetentionCount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-5">
                <p class="text-xs font-semibold text-gray-500">Status: <span class="{{ $backupScheduleEnabled ? 'text-emerald-700' : 'text-gray-500' }}">{{ $backupScheduleEnabled ? 'Enabled' : 'Disabled' }}</span></p>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveBackupSchedule" class="inline-flex items-center gap-2 rounded-2xl bg-[#0e5c3a] px-5 py-2.5 text-xs font-bold text-white transition hover:bg-[#0a4a2e] disabled:opacity-60">
                    <i class="ph ph-floppy-disk"></i><span>Save Schedule</span>
                </button>
            </div>
        </form>

        <section class="rounded-[2rem] border border-gray-100 bg-white p-7 shadow-sm">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-[#0e5c3a]"><i class="ph ph-shield-check"></i></div>
            <h2 class="mt-4 font-heading text-lg font-extrabold text-gray-800">Recovery Readiness</h2>
            @if ($lastSuccessfulBackup)
                <p class="mt-2 text-sm font-bold text-emerald-700">Latest backup completed</p>
                <p class="mt-1 text-xs text-gray-500">{{ $lastSuccessfulBackup->completed_at?->timezone(config('ndmu-rmas.timezone'))->format('F j, Y · g:i A') }}</p>
                <p class="mt-3 break-all font-mono text-[10px] text-gray-400">SHA-256: {{ $lastSuccessfulBackup->sha256 }}</p>
                <p class="mt-3 text-xs font-bold {{ $lastSuccessfulBackup->verification_status === 'verified' ? 'text-emerald-700' : 'text-amber-700' }}">Integrity: {{ $lastSuccessfulBackup->verification_status === 'verified' ? 'Verified' : 'Verification required' }}</p>
            @else
                <p class="mt-3 text-sm font-semibold text-amber-700">No successful backup has been recorded yet.</p>
            @endif
            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800">Download important backups and keep an encrypted copy on another device or storage provider. A backup stored only on this server cannot protect against total device failure.</div>
        </section>
    </div>

    <section class="overflow-hidden rounded-[2rem] border border-gray-100 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-7 py-5">
            <div><h2 class="font-heading text-lg font-extrabold text-gray-800">Available Backups</h2><p class="mt-1 text-xs text-gray-500">Up to 50 recent backup attempts are shown.</p></div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-gray-600">{{ $systemBackups->where('status', 'completed')->count() }} ready</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-left">
                <thead class="bg-[#0e5c3a] text-[10px] uppercase tracking-wider text-white"><tr><th class="px-6 py-3.5">Backup</th><th class="px-6 py-3.5">Size</th><th class="px-6 py-3.5">Created</th><th class="px-6 py-3.5">Status</th><th class="px-6 py-3.5 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($systemBackups as $backup)
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-6 py-4"><p class="text-sm font-extrabold text-gray-800">{{ $backup->filename }}</p><p class="mt-1 text-[10px] text-gray-400">{{ ucfirst($backup->trigger) }}{{ $backup->triggeredBy ? ' · '.$backup->triggeredBy->name : '' }}</p>@if($backup->failure_message)<p class="mt-1 max-w-xl text-xs text-red-600">{{ $backup->failure_message }}</p>@endif</td>
                            <td class="whitespace-nowrap px-6 py-4 text-xs font-semibold text-gray-600">{{ $backup->size_bytes ? number_format($backup->size_bytes / 1024 / 1024, 2).' MB' : '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-600">{{ $backup->started_at->timezone(config('ndmu-rmas.timezone'))->format('M j, Y · g:i A') }}</td>
                            <td class="px-6 py-4"><span @class(['rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider', 'bg-emerald-100 text-emerald-800' => $backup->status === 'completed', 'bg-amber-100 text-amber-800' => $backup->status === 'running', 'bg-red-100 text-red-700' => $backup->status === 'failed'])>{{ $backup->status }}</span>@if($backup->verification_status)<p class="mt-2 text-[9px] font-black uppercase {{ $backup->verification_status === 'verified' ? 'text-emerald-700' : 'text-red-600' }}">{{ $backup->verification_status }}</p>@endif</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                @if ($backup->status === 'completed')<button type="button" wire:click="verifySystemBackup({{ $backup->id }})" wire:loading.attr="disabled" wire:target="verifySystemBackup({{ $backup->id }})" class="inline-flex items-center gap-1 rounded-xl border border-emerald-200 px-3 py-2 text-[10px] font-bold text-emerald-700 hover:bg-emerald-50"><i class="ph ph-shield-check"></i> Verify</button><a href="{{ route('admin.backups.download', $backup) }}" class="ml-1 inline-flex items-center gap-1 rounded-xl bg-[#0e5c3a] px-3 py-2 text-[10px] font-bold text-white hover:bg-[#0a4a2e]"><i class="ph ph-download-simple"></i> Download</a>@endif
                                @if ($backup->status !== 'running')<button type="button" wire:click="deleteSystemBackup({{ $backup->id }})" wire:confirm="Delete this backup permanently?" class="ml-1 inline-flex items-center gap-1 rounded-xl border border-red-200 px-3 py-2 text-[10px] font-bold text-red-600 hover:bg-red-50"><i class="ph ph-trash"></i> Delete</button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-14 text-center"><i class="ph ph-database text-4xl text-gray-300"></i><p class="mt-3 text-sm font-bold text-gray-600">No backups yet</p><p class="mt-1 text-xs text-gray-400">Use “Run Backup Now” to create the first protected archive.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
