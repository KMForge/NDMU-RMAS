@props(['journey'])

@if($journey)
<div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden mb-8 relative">
    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

    <div class="bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#042416] p-6 sm:p-7 text-white mt-1">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-black/25 backdrop-blur-md border border-[#eebc3f]/30 text-[#eebc3f]">
                        13 Required Stages + 1 Optional
                    </span>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider
                        {{ $journey['stage_status'] === 'completed' ? 'bg-emerald-500 text-white' : ($journey['stage_status'] === 'blocked' ? 'bg-rose-500 text-white' : 'bg-blue-600 text-white') }}">
                        {{ ucfirst(str_replace('_', ' ', $journey['stage_status'])) }}
                    </span>
                </div>
                <h3 class="text-xl sm:text-2xl font-black font-heading tracking-tight text-white">
                    Stage {{ $journey['current_stage'] }}: {{ $journey['current_stage_name'] }}
                </h3>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <div class="rounded-2xl border border-white/20 bg-white/10 backdrop-blur-md px-4 py-2 text-right shadow-inner">
                    <div class="text-2xl sm:text-3xl font-black text-[#eebc3f] font-mono">{{ $journey['percentage'] }}%</div>
                    <div class="text-[10px] font-black uppercase tracking-wider text-emerald-200">Overall Progress</div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4 w-full bg-black/30 rounded-full h-3 p-0.5 border border-white/15 overflow-hidden">
            <div class="bg-gradient-to-r from-[#eebc3f] to-[#f4c542] h-full rounded-full transition-all duration-500 shadow-sm"
                 style="width: {{ max(5, $journey['percentage']) }}%"></div>
        </div>
    </div>

    <div class="p-6 sm:p-7 bg-slate-50/70 border-t border-slate-100">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left: Current Stage Summary -->
            <div class="space-y-3">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Current Status & Requirements</div>
                @if(!empty($journey['waiting_on']))
                    <div class="flex items-center gap-3 text-xs text-slate-700 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs font-medium">
                        <i class="ph ph-clock-countdown text-amber-500 text-lg shrink-0"></i>
                        <span>Waiting on: <strong class="text-slate-900 font-bold">{{ $journey['waiting_on'] }}</strong></span>
                    </div>
                @endif

                @if(!empty($journey['blockers']))
                    <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 space-y-1.5">
                        <div class="font-bold flex items-center gap-2 text-rose-900">
                            <i class="ph ph-warning-circle text-base text-rose-600"></i>
                            <span>Action Required / Blocker</span>
                        </div>
                        @foreach($journey['blockers'] as $blocker)
                            <div class="font-medium">• {{ $blocker }}</div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($journey['pending_requirements']))
                    <div class="text-xs text-slate-600 space-y-1 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
                        <span class="font-bold text-slate-800 text-[11px] block mb-1">Pending Actions:</span>
                        <ul class="space-y-1 text-slate-500">
                            @foreach($journey['pending_requirements'] as $req)
                                <li class="flex items-start gap-1.5">
                                    <span class="text-emerald-700 font-bold">•</span>
                                    <span>{{ $req }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Right: Next Action Guidance -->
            <div class="flex flex-col justify-between bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
                <div>
                    <div class="text-[10px] font-black text-[#0e5c3a] uppercase tracking-wider mb-2">Recommended Next Action</div>
                    @if($journey['next_action'])
                        <div class="text-sm font-black text-slate-900 mb-1">
                            {{ $journey['next_action']['label'] }}
                        </div>
                        <p class="text-xs text-slate-500 font-medium">
                            @if(($journey['next_action']['action_type'] ?? 'form') === 'document')
                                Requirement: <span class="font-bold text-slate-800">{{ $journey['next_action']['document_label'] ?? 'Research Document' }}</span>
                            @elseif(($journey['next_action']['action_type'] ?? 'form') === 'presentation')
                                Activity: <span class="font-bold text-slate-800">Title Presentation</span>
                            @else
                                Form: <code class="px-2 py-0.5 bg-slate-100 font-mono text-slate-800 rounded-lg text-xs font-bold">{{ strtoupper($journey['next_action']['form_code'] ?? 'N/A') }}</code>
                            @endif
                            (Role: <span class="capitalize text-[#0e5c3a] font-bold">{{ str_replace('_', ' ', $journey['next_action']['actor_type']) }}</span>)
                        </p>
                    @else
                        <p class="text-xs text-slate-500 font-medium">All current stage requirements are up to date.</p>
                    @endif
                </div>

                @if($journey['next_action'] && !empty($journey['next_action']['route']))
                    <a href="{{ $journey['next_action']['route'] }}"
                       class="inline-flex items-center justify-center px-4 py-2.5 bg-[#0e5c3a] hover:bg-[#073823] text-white font-bold text-xs rounded-xl shadow-md transition-all gap-2 cursor-pointer">
                        <span>Execute Next Step</span>
                        <i class="ph ph-arrow-right text-sm"></i>
                    </a>
                @endif
            </div>
        </div>

        <!-- Research lifecycle timeline -->
        <div class="mt-6 pt-6 border-t border-slate-200">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-3">Lifecycle Stages Timeline</div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-2 text-center">
                @foreach($journey['stages'] as $sNum => $sDetails)
                    <div class="min-h-20 p-2.5 rounded-2xl border text-xs flex flex-col items-center justify-center transition-all
                        {{ $sDetails['is_completed'] ? 'bg-emerald-50 border-emerald-300 text-emerald-950 font-bold shadow-2xs' : (($sDetails['is_optional'] ?? false) ? 'bg-violet-50/60 border-violet-200 border-dashed text-violet-700' : ($sNum === $journey['current_stage'] ? 'bg-emerald-100/60 border-[#0e5c3a] text-[#073823] font-black ring-2 ring-emerald-600/30 shadow-2xs' : 'bg-white border-slate-200 text-slate-400')) }}">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] mb-1 font-black
                            {{ $sDetails['is_completed'] ? 'bg-gradient-to-br from-[#073823] to-[#0e5c3a] text-[#eebc3f]' : ($sNum === $journey['current_stage'] ? 'bg-[#0e5c3a] text-white' : 'bg-slate-100 text-slate-400') }}">
                            {{ $sNum }}
                        </span>
                        <span class="w-full text-[10px] leading-tight whitespace-normal break-words font-semibold" title="{{ $sDetails['name'] }}">{{ $sDetails['name'] }}</span>
                        @if($sDetails['is_auto_completed'] ?? false)
                            <span class="mt-1 text-[8px] font-black uppercase tracking-wider text-emerald-700">Auto-completed for BSIT</span>
                        @endif
                        @if($sDetails['is_optional'] ?? false)
                            <span class="mt-1 text-[8px] font-black uppercase tracking-wider text-violet-600">Optional</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
