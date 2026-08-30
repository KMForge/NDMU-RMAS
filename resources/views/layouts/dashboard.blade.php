@extends('layouts.blank')

@section('content')
<div class="min-h-screen flex font-sans bg-[#f4f7f6]">
    <!-- Left Sidebar: Navigation -->
    <aside class="fixed inset-y-0 left-0 w-72 bg-gradient-to-b from-[#09472d] via-[#0e5c3a] to-[#073622] text-white flex flex-col justify-between z-20 border-r border-emerald-800/40 shadow-2xl overflow-y-auto">
        <div class="flex-shrink-0">
            <!-- Brand Logo Header -->
            <div class="p-6 pb-4 flex items-center gap-3.5">
                <div class="p-2 bg-gradient-to-br from-white/15 to-white/5 rounded-2xl border border-white/20 shadow-lg backdrop-blur-md">
                    <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-10 w-auto drop-shadow-sm">
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-heading font-black text-xl text-white tracking-tight">NDMU</span>
                    <span class="text-[9px] font-black text-[#eebc3f] tracking-[0.16em] uppercase mt-1">Research Management</span>
                </div>
            </div>

            <!-- Designer Decorative Underline under Logo -->
            <div class="px-6 my-2 flex items-center justify-center gap-2">
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-[#eebc3f]/60 to-[#eebc3f]"></div>
                <div class="h-1 w-8 rounded-full bg-gradient-to-r from-[#eebc3f] to-[#ffd76f] shadow-[0_0_8px_rgba(238,188,63,0.7)]"></div>
                <div class="h-px flex-1 bg-gradient-to-l from-transparent via-[#eebc3f]/60 to-[#eebc3f]"></div>
            </div>

            <!-- Floating Profile Card -->
            <div class="px-5 py-3">
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.06] border border-white/10 shadow-inner backdrop-blur-xs hover:bg-white/[0.09] transition-all">
                    <div class="relative w-10 h-10 rounded-xl bg-gradient-to-tr from-[#eebc3f] to-[#ffd76f] text-[#09472d] font-black flex items-center justify-center text-lg flex-shrink-0 shadow-md">
                        {{ auth()->user() ? substr(auth()->user()->name, 0, 1) : 'U' }}
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 border-2 border-[#09472d]"></span>
                        </span>
                    </div>
                    <div class="flex flex-col leading-tight overflow-hidden min-w-0">
                        <span class="font-bold text-xs text-white truncate">{{ auth()->user() ? auth()->user()->name : 'User' }}</span>
                        <span class="text-[10px] text-white/70 font-medium mt-0.5 truncate">{{ auth()->user() && auth()->user()->roles->first() ? str_replace('-', ' ', \Illuminate\Support\Str::title(auth()->user()->roles->first()->name)) : 'Research Member' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex-grow px-5 py-3 space-y-6">
            <div class="space-y-1">
                <div class="flex items-center gap-2 px-3 mb-2.5">
                    <span class="w-1 h-3 rounded-full bg-[#eebc3f]"></span>
                    <span class="text-[10px] font-black tracking-[0.18em] text-emerald-300/80 uppercase">Navigation</span>
                </div>
                
                <a href="{{ route('dashboard') }}" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl bg-[#eebc3f] text-[#09472d] font-bold shadow-md shadow-amber-950/20 text-[13px] transition-all duration-200 group">
                    <div class="flex items-center gap-3">
                        <i class="ph ph-squares-four text-lg transition-transform group-hover:scale-110"></i>
                        <span>Dashboard</span>
                    </div>
                    <span class="w-1.5 h-1.5 rounded-full bg-[#09472d]"></span>
                </a>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="flex-shrink-0 px-5 pb-5 mt-auto">
            <div class="relative flex items-center justify-center my-3">
                <div class="w-full h-px bg-gradient-to-r from-transparent via-white/15 to-transparent"></div>
            </div>
            <div class="space-y-1">
                <form method="POST" action="{{ route('logout') }}" class="block" data-confirm-logout>
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-white/70 hover:text-rose-200 hover:bg-rose-500/20 border border-transparent hover:border-rose-500/30 font-semibold text-[13px] transition-all duration-200 text-left cursor-pointer">
                        <i class="ph ph-sign-out text-lg"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
            <div class="flex items-center justify-center gap-2 text-[9px] text-white/40 text-center font-medium mt-4">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400/60"></span>
                <span>NDMU-RMAS © {{ now()->year }} · v1.0</span>
            </div>
        </div>
    </aside>

    <!-- Right Side: Content Area -->
    <div class="flex-1 flex flex-col min-h-screen pl-72">
        <!-- Top Nav Header -->
        <header class="h-20 bg-white border-b border-gray-150 px-8 flex items-center justify-between sticky top-0 z-40 flex-shrink-0">
            <!-- Search bar -->
            <div class="relative w-96">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                    <i class="ph ph-magnifying-glass text-base"></i>
                </span>
                <input
                    type="text"
                    placeholder="Search research, documents, or tasks..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-100 rounded-full text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:bg-white focus:border-gray-350 transition-all duration-200"
                >
            </div>

            <!-- Right profile area -->
            <div class="flex items-center gap-4">
                <x-notification-dropdown />
                
                <div class="flex items-center gap-3 pl-2 border-l border-gray-150">
                    <div class="w-8 h-8 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-xs">
                        {{ auth()->user() ? substr(auth()->user()->name, 0, 1) : 'U' }}
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="font-bold text-xs text-gray-800">{{ auth()->user() ? auth()->user()->name : 'User' }}</span>
                        <span class="text-[9px] font-bold text-gray-400 mt-0.5">Research Portal</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-grow p-8">
            @yield('dashboard-content')
        </main>
    </div>
</div>
@endsection
