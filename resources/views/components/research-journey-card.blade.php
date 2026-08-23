@props(['journey'])

@if($journey)
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
    <div class="bg-gradient-to-r from-emerald-800 to-emerald-950 p-6 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-700/80 text-emerald-100 border border-emerald-600/50">
                        13-Stage Research Journey
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider
                        {{ $journey['stage_status'] === 'completed' ? 'bg-emerald-500 text-white' : ($journey['stage_status'] === 'blocked' ? 'bg-amber-500 text-white' : 'bg-blue-500 text-white') }}">
                        {{ ucfirst($journey['stage_status']) }}
                    </span>
                </div>
                <h3 class="text-xl font-bold tracking-tight text-white">
                    Stage {{ $journey['current_stage'] }}: {{ $journey['current_stage_name'] }}
                </h3>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <div class="text-2xl font-black text-emerald-400">{{ $journey['percentage'] }}%</div>
                    <div class="text-xs text-emerald-200">Overall Progress</div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4 w-full bg-emerald-950/60 rounded-full h-3 p-0.5 border border-emerald-700/40 overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-400 to-emerald-300 h-full rounded-full transition-all duration-500 shadow-sm"
                 style="width: {{ max(5, $journey['percentage']) }}%"></div>
        </div>
    </div>

    <div class="p-6 bg-slate-50 border-t border-slate-200/80">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left: Current Stage Summary -->
            <div class="space-y-3">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Status & Requirements</div>
                @if(!empty($journey['waiting_on']))
                    <div class="flex items-center gap-2 text-sm text-slate-700 bg-white p-3 rounded-lg border border-slate-200 shadow-xs">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Waiting on: <strong class="text-slate-900 font-semibold">{{ $journey['waiting_on'] }}</strong></span>
                    </div>
                @endif

                @if(!empty($journey['blockers']))
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-sm text-rose-800 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-rose-900">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Action Required / Blocker
                        </div>
                        @foreach($journey['blockers'] as $blocker)
                            <div>• {{ $blocker }}</div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($journey['pending_requirements']))
                    <div class="text-xs text-slate-600 space-y-1">
                        <span class="font-medium text-slate-700">Pending Actions:</span>
                        <ul class="list-disc list-inside text-slate-500 pl-1">
                            @foreach($journey['pending_requirements'] as $req)
                                <li>{{ $req }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Right: Next Action Guidance -->
            <div class="flex flex-col justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
                <div>
                    <div class="text-xs font-semibold text-emerald-800 uppercase tracking-wider mb-2">Recommended Next Action</div>
                    @if($journey['next_action'])
                        <div class="text-sm font-semibold text-slate-900 mb-1">
                            {{ $journey['next_action']['label'] }}
                        </div>
                        <p class="text-xs text-slate-500 mb-4">
                            @if(($journey['next_action']['action_type'] ?? 'form') === 'document')
                                Requirement: <span class="font-semibold text-slate-700">Title Proposal Document</span>
                            @elseif(($journey['next_action']['action_type'] ?? 'form') === 'presentation')
                                Activity: <span class="font-semibold text-slate-700">Title Presentation</span>
                            @else
                                Form: <code class="px-1.5 py-0.5 bg-slate-100 font-mono text-slate-800 rounded">{{ strtoupper($journey['next_action']['form_code'] ?? 'N/A') }}</code>
                            @endif
                            (Role: <span class="capitalize text-emerald-700 font-medium">{{ str_replace('_', ' ', $journey['next_action']['actor_type']) }}</span>)
                        </p>
                    @else
                        <p class="text-xs text-slate-500">All current stage requirements are up to date.</p>
                    @endif
                </div>

                @if($journey['next_action'] && !empty($journey['next_action']['route']))
                    <a href="{{ $journey['next_action']['route'] }}"
                       class="inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg shadow-xs transition-colors gap-2">
                        <span>Execute Next Step</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                @endif
            </div>
        </div>

        <!-- 13-Stage Timeline Preview -->
        <div class="mt-6 pt-6 border-t border-slate-200">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Lifecycle Stages Timeline</div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-7 gap-2 text-center">
                @foreach($journey['stages'] as $sNum => $sDetails)
                    <div class="min-h-20 p-3 rounded-lg border text-xs flex flex-col items-center justify-center transition-colors
                        {{ $sDetails['is_completed'] ? 'bg-emerald-50 border-emerald-300 text-emerald-900 font-semibold' : ($sNum === $journey['current_stage'] ? 'bg-blue-50 border-blue-400 text-blue-900 font-bold ring-2 ring-blue-300/50' : 'bg-white border-slate-200 text-slate-400') }}">
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] mb-1 font-bold
                            {{ $sDetails['is_completed'] ? 'bg-emerald-600 text-white' : ($sNum === $journey['current_stage'] ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500') }}">
                            {{ $sNum }}
                        </span>
                        <span class="w-full text-[11px] leading-4 whitespace-normal break-words" title="{{ $sDetails['name'] }}">{{ $sDetails['name'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif
