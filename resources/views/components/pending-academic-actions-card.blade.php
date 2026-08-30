@props(['pendingActions' => []])

@if(!empty($pendingActions) && count($pendingActions) > 0)
<div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden mb-6">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-100 border border-amber-200 flex items-center justify-center text-amber-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">My Pending Academic Actions</h3>
                <p class="text-xs text-slate-500">Official form actions requiring your explicit academic sign-off</p>
            </div>
        </div>
        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
            {{ count($pendingActions) }} Pending
        </span>
    </div>

    <div class="divide-y divide-slate-100">
        @foreach($pendingActions as $item)
            <div class="p-4 hover:bg-slate-50/80 transition-colors flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center flex-wrap gap-2">
                        <!-- Acting As Role Badge -->
                        <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center gap-1">
                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Acting as {{ $item['actor_type_label'] }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            {{ strtoupper($item['form_code']) }}
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs font-medium text-slate-600">Stage {{ $item['stage'] }}: {{ $item['stage_name'] }}</span>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900">
                        {{ $item['form_title'] }}
                    </h4>

                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        <span>Group: <strong class="text-slate-700">{{ $item['group_name'] }}</strong></span>
                        <span>•</span>
                        <span>Class: <strong class="text-slate-700">{{ $item['class_name'] }}</strong></span>
                        <span>•</span>
                        <span>Updated: <span class="text-slate-600">{{ $item['created_at'] }}</span></span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium uppercase tracking-wider
                        {{ $item['status'] === 'submitted' ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-blue-100 text-blue-800 border border-blue-300' }}">
                        {{ ucfirst($item['status']) }}
                    </span>
                    <a href="{{ $item['route'] }}"
                       class="px-4 py-2 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white font-medium text-xs rounded-xl shadow-xs transition-colors inline-flex items-center gap-1.5">
                        <span>{{ $item['action_label'] }}</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
