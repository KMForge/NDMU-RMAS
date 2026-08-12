@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-3">
        <!-- Results Counter -->
        <div class="text-xs font-semibold text-gray-500">
            Showing <span class="font-extrabold text-gray-800">{{ $paginator->firstItem() }}</span> to <span class="font-extrabold text-gray-800">{{ $paginator->lastItem() }}</span> of <span class="font-extrabold text-gray-800">{{ $paginator->total() }}</span> results
        </div>

        <!-- Pagination Buttons -->
        <div class="inline-flex items-center gap-1.5 bg-white p-1 rounded-2xl border border-gray-200 shadow-xs">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold text-gray-300 bg-gray-50 cursor-not-allowed border border-transparent select-none">
                    <i class="ph ph-caret-left text-sm"></i>
                    <span>Previous</span>
                </span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold text-[#0e5c3a] bg-emerald-50/60 hover:bg-[#0e5c3a] hover:text-white transition-all duration-200 border border-emerald-100 shadow-2xs">
                    <i class="ph ph-caret-left text-sm"></i>
                    <span>Previous</span>
                </button>
            @endif

            {{-- Pagination Elements --}}
            <div class="hidden md:inline-flex items-center gap-1 px-1">
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="px-2 py-1 text-xs font-bold text-gray-400 select-none">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2.5 rounded-xl text-xs font-black bg-[#0e5c3a] text-white shadow-md shadow-emerald-900/15 border border-[#0a4a2e] ring-2 ring-[#0e5c3a]/20 select-none">
                                    {{ $page }}
                                </span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2.5 rounded-xl text-xs font-bold text-gray-700 hover:text-[#0e5c3a] hover:bg-emerald-50 transition-all duration-150 border border-transparent hover:border-emerald-200">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Mobile Current Page Indicator --}}
            <span class="md:hidden px-3 py-1.5 rounded-xl bg-[#0e5c3a] text-white text-xs font-bold shadow-xs select-none">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold text-[#0e5c3a] bg-emerald-50/60 hover:bg-[#0e5c3a] hover:text-white transition-all duration-200 border border-emerald-100 shadow-2xs">
                    <span>Next</span>
                    <i class="ph ph-caret-right text-sm"></i>
                </button>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold text-gray-300 bg-gray-50 cursor-not-allowed border border-transparent select-none">
                    <span>Next</span>
                    <i class="ph ph-caret-right text-sm"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
