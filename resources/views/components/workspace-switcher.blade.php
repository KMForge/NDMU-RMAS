@if (count($workspaces) > 1)
    @php($current = $currentWorkspace !== null ? ($workspaces[$currentWorkspace] ?? null) : null)
    <div class="relative" x-data="{ workspaceMenuOpen: false }" @click.outside="workspaceMenuOpen = false" @keydown.escape.window="workspaceMenuOpen = false">
        <button
            type="button"
            @click="workspaceMenuOpen = !workspaceMenuOpen"
            :aria-expanded="workspaceMenuOpen"
            class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left shadow-xs transition-colors hover:border-[#0e5c3a]/30 hover:bg-emerald-50/40"
        >
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-[#0e5c3a]">
                <i class="ph {{ $current['icon'] ?? 'ph-squares-four' }} text-base"></i>
            </span>
            <span class="hidden leading-tight lg:block">
                <span class="block text-[9px] font-bold uppercase tracking-wider text-gray-400">Workspace</span>
                <span class="block text-[11px] font-black text-gray-700">{{ $current['label'] ?? 'Select workspace' }}</span>
            </span>
            <i class="ph ph-caret-down text-xs text-gray-400 transition-transform" :class="workspaceMenuOpen ? 'rotate-180' : ''"></i>
        </button>

        <div
            x-show="workspaceMenuOpen"
            x-cloak
            x-transition.origin.top.right
            class="absolute right-0 z-50 mt-2 w-72 overflow-hidden rounded-2xl border border-gray-150 bg-white p-2 shadow-xl"
        >
            <div class="px-3 py-2">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-gray-400">Switch Workspace</p>
                <p class="mt-1 text-[11px] text-gray-500">Only workspaces granted by your roles are listed.</p>
            </div>

            <div class="mt-1 space-y-1">
                @foreach ($workspaces as $slug => $workspace)
                    <form method="POST" action="{{ route('workspace.switch', $slug) }}">
                        @csrf
                        <button
                            type="submit"
                            @disabled($slug === $currentWorkspace)
                            @class([
                                'flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition-colors',
                                'cursor-default bg-emerald-50 text-[#0e5c3a]' => $slug === $currentWorkspace,
                                'text-gray-700 hover:bg-gray-50' => $slug !== $currentWorkspace,
                            ])
                        >
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-[#0e5c3a] shadow-xs">
                                <i class="ph {{ $workspace['icon'] }} text-lg"></i>
                            </span>
                            <span class="min-w-0 flex-1 leading-tight">
                                <span class="block text-xs font-black">{{ $workspace['label'] }}</span>
                                <span class="mt-1 block text-[10px] text-gray-500">{{ $workspace['description'] }}</span>
                            </span>
                            @if ($slug === $currentWorkspace)
                                <span class="rounded-full bg-[#0e5c3a] px-2 py-1 text-[8px] font-black uppercase text-white">Current</span>
                            @else
                                <i class="ph ph-arrow-right text-sm text-gray-400"></i>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
@endif
