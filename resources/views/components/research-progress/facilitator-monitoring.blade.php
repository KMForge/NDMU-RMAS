@props(['groups', 'search' => '', 'groupStatus' => 'active'])

<div class="space-y-6">
    <form method="GET" action="{{ route('facilitator.dashboard') }}" class="bg-white rounded-2xl border border-gray-100 p-4 flex flex-wrap gap-3">
        <input type="hidden" name="tab" value="monitoring">
        <input name="progress_search" value="{{ $search }}" maxlength="100" placeholder="Search group or research title..." class="flex-1 min-w-64 rounded-xl border border-gray-200 px-4 py-2.5 text-sm">
        <select name="progress_group_status" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm">
            <option value="active" @selected($groupStatus === 'active')>Active groups</option>
            <option value="disbanded" @selected($groupStatus === 'disbanded')>Disbanded history</option>
        </select>
        <button class="rounded-xl bg-[#0e5c3a] px-5 py-2.5 text-sm font-bold text-white">Filter</button>
    </form>

    @forelse ($groups as $group)
        @php($summary = $group->progress_summary)
        <section class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
            <header class="p-6 border-b border-gray-100 flex flex-wrap justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $group->researchClass?->name }}</p>
                    <h2 class="text-xl font-bold text-gray-900 mt-1">{{ $group->research_title ?: $group->name }}</h2>
                    <p class="text-sm text-gray-500 mt-1">Adviser: {{ $group->adviser?->name ?? 'Not assigned' }} · {{ $group->members->count() }} member(s)</p>
                </div>
                <div class="text-right">
                    <strong class="text-3xl text-emerald-700">{{ number_format($summary['progress_percentage'], 0) }}%</strong>
                    <p class="text-xs text-gray-500">{{ $summary['completed_count'] }} of {{ $summary['applicable_count'] }} applicable milestones</p>
                </div>
            </header>
            <div class="h-2 bg-gray-100"><div class="h-full bg-emerald-600" style="width: {{ $summary['progress_percentage'] }}%"></div></div>

            <div class="p-6 space-y-4">
                @foreach ($summary['milestones'] as $milestone)
                    <article @class([
                        'rounded-2xl border p-5',
                        'border-emerald-200 bg-emerald-50/50' => $milestone->status->value === 'completed',
                        'border-amber-200 bg-amber-50/50' => $milestone->status->value === 'in_progress',
                        'border-gray-200' => $milestone->status->value === 'pending',
                        'border-slate-200 bg-slate-50' => $milestone->status->value === 'not_applicable',
                    ])>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Milestone {{ $milestone->definition->sequence }} · Weight {{ number_format((float) $milestone->definition->weight, 2) }}</p>
                                <h3 class="font-bold text-gray-900">{{ $milestone->definition->name }}</h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $milestone->status->label() }}
                                    @if ($milestone->due_at) · Due {{ $milestone->due_at->format('M j, Y') }} @endif
                                    @if ($milestone->isOverdue()) · <span class="text-red-600 font-bold">Overdue</span> @endif
                                </p>
                                @if ($milestone->remarks)<p class="text-sm text-gray-600 mt-2">{{ $milestone->remarks }}</p>@endif
                                @if ($milestone->not_applicable_reason)<p class="text-sm text-gray-600 mt-2">Reason: {{ $milestone->not_applicable_reason }}</p>@endif
                                @if ($milestone->evidences->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($milestone->evidences as $evidence)
                                            <span class="rounded-full border border-emerald-200 bg-white px-2.5 py-1 text-[10px] font-bold text-emerald-700"
                                                  title="{{ $evidence->summary }}">
                                                <i class="ph ph-seal-check mr-1"></i>{{ str($evidence->evidence_type)->headline() }} verified
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($group->isActive())
                            <details class="mt-4 border-t border-gray-200 pt-3">
                                <summary class="cursor-pointer text-xs font-bold text-gray-500">Administrative override</summary>
                                <div class="grid gap-3 mt-3 md:grid-cols-3">
                                    <div class="space-y-2 rounded-xl border border-amber-100 bg-amber-50/60 p-3">
                                        <p class="text-xs font-bold text-amber-800">Manual status correction</p>
                                        <p class="text-[10px] text-amber-700">Normal progress is synchronized automatically from verified forms, documents, defenses, and evaluations.</p>
                                        @if ($milestone->status->value === 'pending')
                                            <form method="POST" action="{{ route('facilitator.progress.start', $milestone) }}">@csrf @method('PATCH')
                                                <button class="text-xs font-bold text-amber-800">Start manually</button>
                                            </form>
                                        @elseif ($milestone->status->value === 'in_progress')
                                            <form method="POST" action="{{ route('facilitator.progress.complete', $milestone) }}">@csrf @method('PATCH')
                                                <button class="text-xs font-bold text-emerald-700">Complete manually</button>
                                            </form>
                                        @else
                                            <p class="text-[10px] text-gray-500">No manual transition is currently required.</p>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('facilitator.progress.due-date', $milestone) }}" class="space-y-2">@csrf @method('PATCH')
                                        <label class="text-xs font-bold">Due date</label>
                                        <input type="date" name="due_at" value="{{ $milestone->due_at?->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                        <input name="reason" maxlength="2000" placeholder="Reason (optional)" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                        <button class="text-xs font-bold text-blue-700">Save/Clear date</button>
                                    </form>
                                    @if (in_array($milestone->status->value, ['pending', 'in_progress'], true))
                                        <form method="POST" action="{{ route('facilitator.progress.not-applicable', $milestone) }}" class="space-y-2">@csrf @method('PATCH')
                                            <label class="text-xs font-bold">Not applicable</label>
                                            <textarea required name="reason" maxlength="2000" placeholder="Required reason" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"></textarea>
                                            <button class="text-xs font-bold text-slate-700">Mark N/A</button>
                                        </form>
                                    @elseif ($milestone->status->value === 'completed')
                                        <form method="POST" action="{{ route('facilitator.progress.correct', $milestone) }}" class="space-y-2">@csrf @method('PATCH')
                                            <input type="hidden" name="status" value="in_progress">
                                            <label class="text-xs font-bold">Correct completed status</label>
                                            <textarea required name="reason" maxlength="2000" placeholder="Required correction reason" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"></textarea>
                                            <button class="text-xs font-bold text-red-700">Return to In Progress</button>
                                        </form>
                                    @elseif ($milestone->status->value === 'not_applicable')
                                        <form method="POST" action="{{ route('facilitator.progress.correct', $milestone) }}" class="space-y-2">@csrf @method('PATCH')
                                            <input type="hidden" name="status" value="pending">
                                            <label class="text-xs font-bold">Re-enable milestone</label>
                                            <textarea required name="reason" maxlength="2000" placeholder="Required correction reason" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-xs"></textarea>
                                            <button class="text-xs font-bold text-blue-700">Return to Pending</button>
                                        </form>
                                    @endif
                                </div>
                            </details>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="bg-white rounded-3xl border border-gray-100 p-12 text-center text-gray-500">No research groups match this filter.</div>
    @endforelse

    {{ $groups->links() }}
</div>
