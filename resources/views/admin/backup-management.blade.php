<div class="space-y-7">
    <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#286b49] px-7 py-7 text-white shadow-xl shadow-emerald-950/15">
        <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-[#eebc3f]/15 blur-3xl"></div>
        <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#f6cf69]">Protected administration</p>
                <h1 class="mt-2 font-heading text-3xl font-black">Backup Management</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-emerald-50/85">Create, import, verify, download, and restore complete PostgreSQL application-database archives with protected private files.</p>
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

    @if ($pendingDeleteBackup)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" wire:key="delete-backup-confirmation-{{ $pendingDeleteBackup->id }}" role="dialog" aria-modal="true" aria-labelledby="delete-backup-title">
            <button type="button" wire:click="cancelSystemBackupDelete" class="absolute inset-0 bg-slate-950/65 backdrop-blur-sm" aria-label="Cancel backup deletion"></button>
            <section class="relative z-10 w-full max-w-xl overflow-hidden rounded-[2rem] bg-white shadow-2xl shadow-black/30">
                <div class="bg-gradient-to-br from-red-700 to-red-900 px-6 py-6 text-white sm:px-7">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-2xl"><i class="ph ph-trash"></i></span>
                        <div><p class="text-[10px] font-black uppercase tracking-[0.2em] text-red-100">Permanent file deletion</p><h2 id="delete-backup-title" class="mt-1 font-heading text-2xl font-black">Delete this backup?</h2></div>
                    </div>
                </div>
                <div class="space-y-5 p-6 sm:p-7">
                    <p class="text-sm leading-6 text-gray-600">Only <strong class="break-all text-gray-900">{{ $pendingDeleteBackup->filename }}</strong> will be deleted. Roles, users, database records, and other backups are not part of this operation.</p>
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs leading-5 text-red-900"><i class="ph ph-warning-circle mr-1 text-base"></i>This archive cannot be recovered after deletion. Type the exact confirmation below to continue.</div>
                    <label for="backup-delete-confirmation" class="block text-xs font-bold text-gray-700">Type <span class="font-mono text-red-700">DELETE {{ $pendingDeleteBackup->filename }}</span></label>
                    <input id="backup-delete-confirmation" type="text" wire:model="backupDeleteConfirmation" autocomplete="off" class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 font-mono text-xs focus:border-red-500 focus:outline-none focus:ring-4 focus:ring-red-500/10">
                    @error('backupDeleteConfirmation') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="cancelSystemBackupDelete" class="rounded-xl border border-gray-200 px-5 py-3 text-xs font-bold text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="button" wire:click="deleteSystemBackup" wire:loading.attr="disabled" wire:target="deleteSystemBackup" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-700 px-5 py-3 text-xs font-black text-white hover:bg-red-800 disabled:cursor-wait disabled:opacity-60"><i class="ph ph-trash" wire:loading.remove wire:target="deleteSystemBackup"></i><i class="ph ph-spinner-gap animate-spin" wire:loading wire:target="deleteSystemBackup"></i><span wire:loading.remove wire:target="deleteSystemBackup">Permanently delete backup</span><span wire:loading wire:target="deleteSystemBackup">Deleting...</span></button>
                    </div>
                </div>
            </section>
        </div>
    @endif

    @if ($pendingRestoreBackup)
        <section class="rounded-[2rem] border-2 border-amber-300 bg-amber-50 p-6 shadow-sm">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="max-w-3xl">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500 text-xl text-white"><i class="ph ph-warning"></i></span>
                        <div><p class="text-[10px] font-black uppercase tracking-[0.16em] text-amber-700">Destructive recovery action</p><h2 class="font-heading text-xl font-black text-gray-900">Confirm complete database restore</h2></div>
                    </div>
                    <p class="mt-4 text-sm leading-6 text-amber-950">This replaces the current application database with <strong>{{ $pendingRestoreBackup->filename }}</strong>. The system creates a mandatory backup of the current database before restoring.</p>
                    <div class="mt-4 flex flex-wrap gap-2 text-[10px] font-black uppercase tracking-wider">
                        <span class="rounded-full bg-white px-3 py-1.5 text-emerald-700">{{ str_replace('_', ' ', $pendingRestoreBackup->manifest['database_scope'] ?? 'legacy complete dump') }}</span>
                        @if (isset($pendingRestoreBackup->manifest['database_public_table_count']))<span class="rounded-full bg-white px-3 py-1.5 text-gray-700">{{ number_format($pendingRestoreBackup->manifest['database_public_table_count']) }} public tables</span>@endif
                        @if (($pendingRestoreBackup->manifest['includes_private_files'] ?? false) === true)<span class="rounded-full bg-white px-3 py-1.5 text-gray-700">{{ number_format($pendingRestoreBackup->manifest['private_file_count'] ?? 0) }} private files</span>@endif
                    </div>
                </div>
                <div class="w-full max-w-xl rounded-2xl border border-amber-200 bg-white p-5">
                    <label for="backup-restore-confirmation" class="block text-xs font-bold text-gray-700">Type <span class="font-mono text-red-700">RESTORE {{ $pendingRestoreBackup->filename }}</span></label>
                    <input id="backup-restore-confirmation" type="text" wire:model="backupRestoreConfirmation" autocomplete="off" class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 font-mono text-xs focus:border-red-500 focus:outline-none focus:ring-4 focus:ring-red-500/10">
                    @error('backupRestoreConfirmation') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" wire:click="cancelSystemBackupRestore" class="rounded-xl border border-gray-200 px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50">Cancel</button>
                        <button type="button" wire:click="restoreSystemBackup" wire:loading.attr="disabled" wire:target="restoreSystemBackup" class="inline-flex items-center gap-2 rounded-xl bg-red-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-red-800 disabled:cursor-wait disabled:opacity-60"><i class="ph ph-arrow-counter-clockwise"></i><span wire:loading.remove wire:target="restoreSystemBackup">Restore Complete Database</span><span wire:loading wire:target="restoreSystemBackup">Restoring...</span></button>
                    </div>
                </div>
            </div>
        </section>
    @endif

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

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
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
                <div>
                    <label for="backup-import-limit" class="mb-2 block text-xs font-bold uppercase tracking-wider text-gray-600">Import Limit (MB)</label>
                    <input id="backup-import-limit" type="number" min="1" max="{{ config('backups.max_import_limit_mb', 1024) }}" wire:model="backupMaxImportMb" class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:outline-none focus:ring-4 focus:ring-[#0e5c3a]/5">
                    @error('backupMaxImportMb') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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

    <form wire:submit="importSystemBackup" class="rounded-[2rem] border border-gray-100 bg-white p-7 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-700"><i class="ph ph-upload-simple"></i></span><div><h2 class="font-heading text-lg font-extrabold text-gray-800">Import Existing Backup</h2><p class="mt-1 text-xs leading-5 text-gray-500">Upload an NDMU-RMAS ZIP archive up to {{ number_format($backupImportLimitMb) }} MB. It is verified and stored safely; importing does not restore it automatically.</p>@if ($backupServerUploadLimitMb && $backupServerUploadLimitMb < $backupImportLimitMb)<p class="mt-1 text-xs font-semibold text-amber-700">Server upload configuration currently allows only {{ number_format($backupServerUploadLimitMb) }} MB.</p>@endif</div></div>
            </div>
            <div class="flex w-full max-w-2xl flex-col gap-3 sm:flex-row sm:items-start">
                <div class="min-w-0 flex-1">
                    <input type="file" wire:model="backupImportFile" accept=".zip,application/zip" class="block w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs file:mr-4 file:rounded-xl file:border-0 file:bg-[#0e5c3a] file:px-4 file:py-2 file:text-xs file:font-bold file:text-white">
                    <p wire:loading wire:target="backupImportFile" class="mt-2 text-xs font-semibold text-blue-700">Uploading archive...</p>
                    @error('backupImportFile') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled" wire:target="backupImportFile,importSystemBackup" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-blue-700 px-5 py-3 text-xs font-bold text-white hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60"><i class="ph ph-shield-check"></i><span wire:loading.remove wire:target="importSystemBackup">Import & Verify</span><span wire:loading wire:target="importSystemBackup">Verifying...</span></button>
            </div>
        </div>
    </form>

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
                        <tr wire:key="system-backup-row-{{ $backup->id }}" class="hover:bg-gray-50/70">
                            <td class="px-6 py-4"><p class="text-sm font-extrabold text-gray-800">{{ $backup->filename }}</p><p class="mt-1 text-[10px] text-gray-400">{{ match($backup->trigger) { 'pre_restore' => 'Pre-restore safety', 'imported' => 'Imported', default => ucfirst($backup->trigger) } }}{{ $backup->triggeredBy ? ' · '.$backup->triggeredBy->name : '' }}</p><div class="mt-2 flex flex-wrap gap-1"><span class="rounded-full bg-emerald-50 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-emerald-700">{{ ($backup->manifest['database_scope'] ?? null) === 'complete_schema_and_data' ? 'Complete DB' : 'Legacy DB dump' }}</span>@if (($backup->manifest['includes_private_files'] ?? false) === true)<span class="rounded-full bg-blue-50 px-2 py-1 text-[8px] font-black uppercase tracking-wider text-blue-700">{{ number_format($backup->manifest['private_file_count'] ?? 0) }} private files</span>@endif</div>@if($backup->failure_message)<p class="mt-1 max-w-xl text-xs text-red-600">{{ $backup->failure_message }}</p>@endif</td>
                            <td class="whitespace-nowrap px-6 py-4 text-xs font-semibold text-gray-600">{{ $backup->size_bytes ? number_format($backup->size_bytes / 1024 / 1024, 2).' MB' : '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-xs text-gray-600">{{ $backup->started_at->timezone(config('ndmu-rmas.timezone'))->format('M j, Y · g:i A') }}</td>
                            <td class="px-6 py-4"><span @class(['rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider', 'bg-emerald-100 text-emerald-800' => $backup->status === 'completed', 'bg-amber-100 text-amber-800' => $backup->status === 'running', 'bg-red-100 text-red-700' => $backup->status === 'failed'])>{{ $backup->status }}</span>@if($backup->verification_status)<p class="mt-2 text-[9px] font-black uppercase {{ $backup->verification_status === 'verified' ? 'text-emerald-700' : 'text-red-600' }}">{{ $backup->verification_status }}</p>@endif @if($backup->restore_status)<p class="mt-1 text-[9px] font-black uppercase {{ $backup->restore_status === 'restored' ? 'text-blue-700' : 'text-red-600' }}">Restore: {{ $backup->restore_status }}</p>@endif</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                @if ($backup->status === 'completed')<button type="button" wire:click="verifySystemBackup({{ $backup->id }})" wire:loading.attr="disabled" wire:target="verifySystemBackup({{ $backup->id }})" class="inline-flex items-center gap-1 rounded-xl border border-emerald-200 px-3 py-2 text-[10px] font-bold text-emerald-700 hover:bg-emerald-50"><i class="ph ph-shield-check"></i> Verify</button><a href="{{ route('admin.backups.download', $backup) }}" class="ml-1 inline-flex items-center gap-1 rounded-xl bg-[#0e5c3a] px-3 py-2 text-[10px] font-bold text-white hover:bg-[#0a4a2e]"><i class="ph ph-download-simple"></i> Download</a>@if ($backup->verification_status === 'verified')<button type="button" wire:click="prepareSystemBackupRestore({{ $backup->id }})" class="ml-1 inline-flex items-center gap-1 rounded-xl border border-amber-300 px-3 py-2 text-[10px] font-bold text-amber-800 hover:bg-amber-50"><i class="ph ph-arrow-counter-clockwise"></i> Restore</button>@endif @endif
                                @if ($backup->status !== 'running')<button type="button" wire:click="prepareSystemBackupDelete({{ $backup->id }})" wire:loading.attr="disabled" wire:target="prepareSystemBackupDelete({{ $backup->id }})" class="ml-1 inline-flex items-center gap-1 rounded-xl border border-red-200 px-3 py-2 text-[10px] font-bold text-red-600 hover:bg-red-50 disabled:opacity-50"><i class="ph ph-trash"></i> Delete</button>@endif
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
