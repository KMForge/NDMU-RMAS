@extends('layouts.blank')

@php
    $allowedTabs = ['dashboard', 'pending', 'manuscript', 'schedule', 'repository', 'notifications', 'settings'];
    $activeTab = in_array(request()->query('tab'), $allowedTabs, true) ? request()->query('tab') : 'dashboard';
    $tabTitles = [
        'dashboard' => ['College Research Overview', 'Live records within your authorized college scope.'],
        'pending' => ['Pending Approvals', 'Official academic actions currently awaiting your decision.'],
        'manuscript' => ['Manuscripts', 'Current research documents submitted by groups in your college.'],
        'schedule' => ['Defense Schedules', 'Recorded defense schedules and assigned panels for your college.'],
        'repository' => ['Research Repository', 'Authorized current documents from college research groups.'],
        'notifications' => ['Notifications', 'System events and action reminders sent to your account.'],
        'settings' => ['Account Settings', 'Manage your profile, photo, security, and signature.'],
    ];
    [$pageTitle, $pageDescription] = $tabTitles[$activeTab];
@endphp

@section('content')
<div data-portal-shell class="min-h-screen bg-[#f4f7f6]">
    <x-dean-sidebar :active="$activeTab" :badges="$sidebarBadges" :stats="$deanStats" />

    <div data-portal-content class="min-h-screen pl-72">
        <header data-portal-header class="sticky top-0 z-20 flex min-h-20 items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-5 py-3 shadow-sm backdrop-blur sm:px-6 lg:px-8">
            <div class="min-w-0 flex-1"><p class="truncate text-[9px] font-black uppercase tracking-[.15em] text-[#0e5c3a] sm:text-[10px]">{{ $deanCollege?->name ?? 'College profile not assigned' }}</p><h1 class="truncate text-lg font-black text-slate-900 sm:text-xl">{{ $pageTitle }}</h1></div>
            <div class="flex shrink-0 items-center gap-2 sm:gap-3"><x-workspace-switcher current="dean" /><x-notification-dropdown /><div class="hidden items-center gap-2 border-l border-slate-200 pl-3 md:flex"><div class="h-9 w-9 overflow-hidden rounded-xl bg-amber-400 text-emerald-950"><x-current-user-avatar :user="$dean" rounded="rounded-xl" /></div><div class="max-w-36 min-w-0 leading-tight"><p class="truncate text-xs font-black">{{ $dean->displayFirstName() }}</p><p class="mt-0.5 text-[9px] font-semibold uppercase tracking-wide text-slate-400">College Dean</p></div></div></div>
        </header>

        <main data-portal-main class="space-y-6 p-4 sm:p-6 lg:p-8">
            <section data-dashboard-hero class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#0a462c] p-5 text-white shadow-lg sm:p-7">
                <div class="pointer-events-none absolute -right-12 -top-20 h-56 w-56 rounded-full bg-white/[.06]"></div>
                <div class="relative"><p class="text-[10px] font-black uppercase tracking-[.18em] text-[#eebc3f]">College Dean Portal</p><h2 class="mt-2 text-2xl font-black sm:text-3xl">{{ $pageTitle }}</h2><p class="mt-1.5 max-w-2xl text-sm leading-6 text-emerald-100">{{ $pageDescription }}</p></div>
            </section>

            @if ($deanCollege === null)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">Your faculty profile is not assigned to a department and college. Ask an administrator to complete that assignment before college records can be displayed.</div>
            @endif

            @if ($activeTab === 'dashboard')
                <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['Research Groups', $deanStats['groups'], 'ph-users-three', 'text-emerald-700 bg-emerald-50', 'repository'],
                        ['Current Documents', $deanStats['documents'], 'ph-files', 'text-blue-700 bg-blue-50', 'manuscript'],
                        ['Pending Documents', $deanStats['pending_documents'], 'ph-clock', 'text-amber-700 bg-amber-50', 'manuscript'],
                        ['Upcoming Defenses', $deanStats['upcoming_defenses'], 'ph-calendar-check', 'text-purple-700 bg-purple-50', 'schedule'],
                    ] as [$label, $value, $icon, $color, $target])
                        <a href="{{ route('dean.dashboard', ['tab' => $target]) }}" wire:navigate class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md"><div class="flex items-center justify-between gap-4"><div><p class="text-[11px] font-black uppercase tracking-wide text-slate-400">{{ $label }}</p><p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($value) }}</p></div><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $color }}"><i class="ph {{ $icon }} text-2xl"></i></span></div><p class="mt-3 text-[11px] font-bold text-emerald-700 opacity-0 transition group-hover:opacity-100">View records &rarr;</p></a>
                    @endforeach
                </section>
                <x-pending-academic-actions-card :pendingActions="$pendingAcademicActions ?? []" />
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="font-black">Recent College Research Groups</h3><p class="mt-1 text-xs text-slate-500">Latest database records in your college.</p></div><a href="{{ route('dean.dashboard', ['tab' => 'repository']) }}" wire:navigate class="inline-flex w-fit items-center gap-1 text-xs font-black text-[#0e5c3a] hover:text-emerald-800">Open repository <span aria-hidden="true">&rarr;</span></a></div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($deanGroups->take(6) as $group)
                            <div class="flex flex-col justify-between gap-3 p-5 transition hover:bg-slate-50 sm:flex-row sm:items-center"><div class="min-w-0"><p class="break-words font-black text-slate-900">{{ $group->researchGroup?->currentProject?->title ?: $group->name }}</p><p class="mt-1 text-xs leading-5 text-slate-500">{{ $group->researchClass?->name ?: 'Class not assigned' }} <span aria-hidden="true">&middot;</span> {{ $group->members_count }} {{ Str::plural('member', $group->members_count) }} <span aria-hidden="true">&middot;</span> Adviser: {{ $group->adviser?->name ?? 'Not assigned' }}</p></div><span class="w-fit shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-black uppercase text-emerald-700">{{ str($group->status)->headline() }}</span></div>
                        @empty <p class="p-8 text-center text-sm text-slate-500">No research groups are recorded for this college.</p> @endforelse
                    </div>
                </section>
            @elseif ($activeTab === 'pending')
                @if (collect($pendingAcademicActions ?? [])->isNotEmpty())
                    <x-pending-academic-actions-card :pendingActions="$pendingAcademicActions" />
                @else
                    <div class="rounded-2xl border border-slate-200 bg-white px-5 py-14 text-center shadow-sm"><span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-3xl text-emerald-600"><i class="ph ph-check-circle"></i></span><h3 class="mt-4 text-lg font-black text-slate-900">No approvals waiting</h3><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">You are caught up. New forms requiring the Dean's decision will automatically appear here.</p><a href="{{ route('official-forms.workspace.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl border border-emerald-200 px-4 py-2.5 text-xs font-black text-emerald-700 hover:bg-emerald-50"><i class="ph ph-file-text"></i>Open Forms Workspace</a></div>
                @endif
            @elseif (in_array($activeTab, ['manuscript', 'repository'], true))
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-slate-100 p-5 xl:flex-row xl:items-end xl:justify-between">
                        <div><h3 class="font-black">{{ $activeTab === 'manuscript' ? 'Current Manuscript Submissions' : 'College Document Repository' }}</h3><p class="mt-1 text-xs text-slate-500">These entries come directly from current document records.</p></div>
                        <form method="GET" action="{{ route('dean.dashboard') }}" class="grid w-full gap-2 sm:grid-cols-[minmax(0,1fr)_12rem_auto] xl:max-w-2xl">
                            <input type="hidden" name="tab" value="{{ $activeTab }}">
                            <label class="sr-only" for="document-search">Search documents</label>
                            <input id="document-search" name="document_search" value="{{ $deanFilters['document_search'] }}" placeholder="Search filename..." class="min-w-0 rounded-xl border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700">
                            <label class="sr-only" for="document-status">Document status</label>
                            <select id="document-status" name="document_status" class="rounded-xl border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700">
                                <option value="all">All statuses</option>
                                @foreach (\App\Enums\DocumentStatus::cases() as $status)
                                    <option value="{{ $status->value }}" @selected($deanFilters['document_status'] === $status->value)>{{ str($status->value)->headline() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#09472d]">Filter</button>
                        </form>
                    </div>
                    <div class="hidden md:block" data-responsive-table-container tabindex="0" aria-label="College documents table. Scroll horizontally for all columns."><table data-responsive-table class="w-full min-w-[820px] text-left text-sm"><thead class="bg-[#0e5c3a] text-[11px] uppercase tracking-wide text-white"><tr><th class="p-4">Document</th><th class="p-4">Group / Class</th><th class="p-4">Stage</th><th class="p-4">Status</th><th class="p-4">Submitted</th><th class="p-4 text-right">Access</th></tr></thead><tbody class="divide-y divide-slate-100">
                        @forelse ($deanDocuments as $document)
                            <tr class="hover:bg-slate-50"><td class="p-4"><p class="max-w-xs truncate font-bold">{{ $document->original_filename }}</p><p class="text-xs text-slate-500">{{ $document->user?->name ?? 'Unknown uploader' }} <span aria-hidden="true">&middot;</span> {{ $document->formattedFileSize() }}</p></td><td class="p-4"><p class="font-semibold">{{ $document->researchClassGroup?->name ?: 'Not assigned' }}</p><p class="text-xs text-slate-500">{{ $document->researchClassGroup?->researchClass?->name ?: 'Class not assigned' }}</p></td><td class="p-4">{{ $document->stageLabel() }}</td><td class="p-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase">{{ str($document->status?->value ?? $document->status)->headline() }}</span></td><td class="p-4 text-xs text-slate-500">{{ $document->submitted_at?->format('M d, Y h:i A') ?? 'Not submitted' }}</td><td class="p-4 text-right">@can('download', $document)<a href="{{ route('documents.view', $document) }}" target="_blank" rel="noopener" class="rounded-lg bg-[#0e5c3a] px-3 py-2 text-xs font-bold text-white hover:bg-[#09472d]">View</a>@else<span class="text-xs text-slate-400">Restricted</span>@endcan</td></tr>
                        @empty <tr><td colspan="6" class="p-10 text-center text-slate-500">No documents match the selected filters.</td></tr> @endforelse
                    </tbody></table></div>
                    <div class="divide-y divide-slate-100 md:hidden">
                        @forelse ($deanDocuments as $document)
                            @php
                                $status = $document->status?->value ?? (string) $document->status;
                            @endphp
                            <article class="space-y-3 p-4">
                                <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="break-words text-sm font-black text-slate-900">{{ $document->original_filename }}</p><p class="mt-1 text-xs text-slate-500">{{ $document->user?->name ?? 'Unknown uploader' }} &middot; {{ $document->formattedFileSize() }}</p></div><span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[9px] font-black uppercase text-slate-700">{{ str($status)->headline() }}</span></div>
                                <dl class="grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3 text-xs"><div><dt class="text-slate-400">Group</dt><dd class="mt-1 font-bold text-slate-700">{{ $document->researchClassGroup?->name ?: 'Not assigned' }}</dd></div><div><dt class="text-slate-400">Stage</dt><dd class="mt-1 font-bold text-slate-700">{{ $document->stageLabel() }}</dd></div><div class="col-span-2"><dt class="text-slate-400">Submitted</dt><dd class="mt-1 font-bold text-slate-700">{{ $document->submitted_at?->format('M d, Y h:i A') ?? 'Not submitted' }}</dd></div></dl>
                                @can('download', $document)<a href="{{ route('documents.view', $document) }}" target="_blank" rel="noopener" class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#0e5c3a] px-4 py-2.5 text-xs font-black text-white"><i class="ph ph-eye"></i>View Document</a>@else<span class="block text-center text-xs font-semibold text-slate-400">Access restricted</span>@endcan
                            </article>
                        @empty <p class="p-10 text-center text-sm text-slate-500">No documents match the selected filters.</p> @endforelse
                    </div>
                </section>
            @elseif ($activeTab === 'schedule')
                <form method="GET" action="{{ route('dean.dashboard') }}" class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <input type="hidden" name="tab" value="schedule">
                    <div><p class="text-sm font-black">Schedule filter</p><p class="text-xs text-slate-500">Show defenses by recorded status.</p></div>
                    <div class="flex flex-col gap-2 sm:flex-row"><select name="schedule_status" class="rounded-xl border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700">@foreach (['all' => 'All statuses', 'pending' => 'Pending', 'current' => 'Current', 'scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)<option value="{{ $value }}" @selected($deanFilters['schedule_status'] === $value)>{{ $label }}</option>@endforeach</select><button class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-sm font-bold text-white">Apply</button></div>
                </form>
                <section @class(['grid gap-4', 'xl:grid-cols-2' => $deanDefenseSchedules->count() > 1])>
                    @forelse ($deanDefenseSchedules as $schedule)
                        @php $defense = $schedule->defense; $group = $defense?->group; @endphp
                        <article class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="border-l-4 border-amber-400 p-5"><div class="flex min-w-0 flex-col justify-between gap-4 sm:flex-row"><div class="min-w-0"><div class="flex flex-wrap gap-2"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase text-emerald-700">{{ str($defense?->defense_type ?? 'defense')->headline() }}</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase text-slate-600">{{ str($schedule->status)->headline() }}</span></div><h3 class="mt-3 break-words text-base font-black">{{ $group?->researchGroup?->currentProject?->title ?: ($group?->name ?? 'Research group') }}</h3><p class="mt-1 text-xs text-slate-500">{{ $group?->researchClass?->name ?: 'Class not assigned' }} <span aria-hidden="true">&middot;</span> {{ $group?->name ?: 'Group not assigned' }}</p></div><div class="shrink-0 rounded-xl bg-slate-50 p-3 text-sm sm:min-w-52 sm:text-right"><p class="font-black text-slate-900">{{ $schedule->starts_at?->format('M d, Y') ?? 'Date not set' }}</p><p class="mt-1 text-xs font-semibold text-slate-600">{{ $schedule->starts_at?->format('h:i A') ?? 'Time not set' }}</p><p class="mt-1 text-xs text-slate-500">{{ $schedule->room?->name ?? $schedule->room?->code ?? 'Venue not assigned' }}</p></div></div><div class="mt-4 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-600"><span class="font-bold text-slate-800">Panel:</span> {{ $defense?->activePanelAssignments?->pluck('user.name')->filter()->join(', ') ?: 'Not assigned' }}</div></div></article>
                    @empty <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500 xl:col-span-2">No defense schedules match the selected filter.</div> @endforelse
                </section>
            @elseif ($activeTab === 'notifications')
                <x-notifications.center :notifications="$userNotifications ?? collect()" :unread-count="$userUnreadCount ?? 0" :filter="$notificationFilter ?? 'all'" :dashboard-route="route('dean.dashboard')" />
            @elseif ($activeTab === 'settings')
                @include('partials.settings')
            @endif
        </main>
    </div>
</div>
@endsection
