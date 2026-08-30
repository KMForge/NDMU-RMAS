<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Notre Dame of Marbel University Research Management and Archiving System (NDMU-RMAS). Centralizing university research proposals, reviews, defenses, and institutional archiving.">
    <title>NDMU-RMAS - Notre Dame of Marbel University Research Management System</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        .site-header { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .site-header.is-scrolled {
            background-color: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px -10px rgba(7, 56, 35, 0.15);
            border-bottom: 1px solid rgba(7, 56, 35, 0.12);
        }
    </style>
</head>
<body class="m-0 overflow-x-hidden bg-[#073823] text-white antialiased selection:bg-[#eebc3f] selection:text-[#073823]">

<!-- Global Sticky University Header -->
<header data-site-header class="site-header sticky top-0 z-50 bg-white border-b border-slate-200/80 transition-all duration-300">
    <div class="mx-auto flex min-h-[76px] max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <!-- University Brand Logo -->
        <a href="{{ route('home') }}" class="group flex items-center gap-3.5 transition-transform duration-200 hover:scale-[1.01]" aria-label="NDMU Research Management System">
            <img src="{{ asset('images/ndmu_logo.png') }}" alt="NDMU Logo" width="48" height="48" class="h-11 w-11 sm:h-12 sm:w-12 shrink-0 object-contain transition-transform duration-300 group-hover:scale-105 drop-shadow-xs">
            <div class="flex flex-col leading-none border-l-2 border-[#eebc3f] pl-3">
                <div class="flex items-center gap-2">
                    <span class="font-heading font-black text-lg sm:text-xl text-[#073823] tracking-tight">NDMU</span>
                    <span class="px-2 py-0.5 rounded-full bg-[#eebc3f]/20 border border-[#eebc3f]/50 text-[#073823] font-black text-[9px] uppercase tracking-wider hidden sm:inline-block">RMAS</span>
                </div>
                <span class="text-[9px] sm:text-[10px] font-black text-[#0e5c3a] tracking-[0.22em] uppercase mt-0.5">Research Management</span>
            </div>
        </a>

        <!-- Auth Action Buttons -->
        <div class="flex items-center gap-3">
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-[#073823] hover:bg-[#0e5c3a] px-5 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-950/20 transition duration-200 hover:-translate-y-0.5"
                >
                    <i class="ph ph-squares-four text-sm text-[#eebc3f]"></i>
                    <span>Go to Dashboard</span>
                </a>
            @else
                @if(Route::has('login'))
                    <a
                        href="{{ route('login') }}"
                        class="px-4 py-2.5 text-xs font-black text-[#073823] hover:text-[#0e5c3a] transition duration-200"
                    >
                        Sign In
                    </a>
                @endif
                @if(Route::has('register'))
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 px-4.5 py-2.5 text-xs font-black text-[#073823] shadow-md shadow-amber-950/15 transition duration-200 hover:-translate-y-0.5"
                    >
                        <i class="ph ph-user-plus text-sm"></i>
                        <span>Register Account</span>
                    </a>
                @endif
            @endauth
        </div>
    </div>
</header>

<main class="bg-[#073823]">
    <!-- HERO SECTION: Full Screen Viewport with Clean Down-Arrow Indicator -->
    <section class="relative min-h-[calc(100vh-76px)] flex flex-col justify-between bg-[#073823] text-white overflow-hidden">
        <!-- Subtle Single-Tone Grid Overlay -->
        <div class="pointer-events-none absolute inset-0 opacity-10">
            <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
                <defs>
                    <pattern id="hero-grid-clean" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(238,188,63,0.4)" stroke-width="0.8" />
                        <circle cx="40" cy="40" r="1" fill="rgba(255,255,255,0.7)" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#hero-grid-clean)" />
            </svg>
        </div>

        <!-- Main Hero Container Vertically Centered -->
        <div class="relative z-10 mx-auto my-auto w-full max-w-7xl grid items-center gap-10 px-6 py-8 sm:py-12 lg:grid-cols-12 lg:px-8">
            
            <!-- Left Hero Content -->
            <div class="relative z-10 lg:col-span-7 space-y-6">
                <!-- Eyebrow Pill -->
                <div class="inline-flex items-center gap-2.5 rounded-full border border-[#eebc3f]/40 bg-black/25 px-4 py-2 text-xs font-black uppercase tracking-[0.18em] text-[#eebc3f] shadow-sm">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#eebc3f] opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-[#eebc3f]"></span>
                    </span>
                    <span>NDMU Institutional Research Portal</span>
                </div>

                <!-- Main Hero Headline -->
                <h1 class="text-4xl font-black leading-[1.12] tracking-tight sm:text-5xl lg:text-[3.5rem] text-white">
                    Manage research from 
                    <span class="text-[#eebc3f]">proposal</span> 
                    to institutional 
                    <span class="text-[#eebc3f]">archiving.</span>
                </h1>

                <!-- Supporting Description -->
                <p class="max-w-xl text-sm sm:text-base leading-relaxed text-emerald-100/90 font-medium">
                    A university-grade research ecosystem connecting student researchers, faculty advisers, review panelists, facilitators, and academic deans in one seamless platform.
                </p>

                <!-- Action CTA Buttons -->
                <div class="flex flex-col gap-3.5 sm:flex-row sm:items-center pt-1">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="group inline-flex min-h-[52px] items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 px-8 text-sm font-black text-[#073823] shadow-xl shadow-black/20 transition duration-200 hover:-translate-y-0.5"
                        >
                            <span>Open Your Workspace</span>
                            <i class="ph ph-arrow-right text-base font-bold transition-transform duration-200 group-hover:translate-x-1"></i>
                        </a>
                    @else
                        @if(Route::has('login'))
                            <a
                                href="{{ route('login') }}"
                                class="group inline-flex min-h-[52px] items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-[#eebc3f] to-[#f4c542] hover:brightness-105 px-8 text-sm font-black text-[#073823] shadow-xl shadow-black/20 transition duration-200 hover:-translate-y-0.5"
                            >
                                <span>Sign In to Continue</span>
                                <i class="ph ph-arrow-right text-base font-bold transition-transform duration-200 group-hover:translate-x-1"></i>
                            </a>
                        @endif
                        @if(Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/10 hover:bg-white/20 px-7 text-sm font-bold text-white transition duration-200 hover:-translate-y-0.5"
                            >
                                <i class="ph ph-student text-base text-[#eebc3f]"></i>
                                <span>Register Student Account</span>
                            </a>
                        @endif
                    @endauth
                </div>

                <!-- 3 Metric Highlights Strip -->
                <div class="grid grid-cols-3 gap-4 border-t border-white/15 pt-6 max-w-lg">
                    <div>
                        <span class="block text-2xl sm:text-3xl font-black text-[#eebc3f]">25</span>
                        <span class="mt-0.5 block text-[11px] font-bold uppercase tracking-wider text-emerald-100/80">Official Forms</span>
                    </div>
                    <div>
                        <span class="block text-2xl sm:text-3xl font-black text-[#eebc3f]">13</span>
                        <span class="mt-0.5 block text-[11px] font-bold uppercase tracking-wider text-emerald-100/80">Journey Stages</span>
                    </div>
                    <div>
                        <span class="block text-2xl sm:text-3xl font-black text-[#eebc3f]">100%</span>
                        <span class="mt-0.5 block text-[11px] font-bold uppercase tracking-wider text-emerald-100/80">Secure Vault</span>
                    </div>
                </div>
            </div>

            <!-- Right Interactive Live Record Showcase Card -->
            <div class="relative lg:col-span-5 lg:justify-self-end w-full max-w-[480px]">
                <div class="relative rounded-3xl border border-emerald-600/30 bg-white p-6 sm:p-7 text-slate-800 shadow-2xl shadow-black/40 overflow-hidden">
                    <!-- Card Top Accent Stripe -->
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#073823]"></div>

                    <!-- Header Row -->
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-2.5">
                            <x-ndmu-n-logo size="sm" :showGlow="false" />
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-[#073823]">Active Manuscript</p>
                                <p class="font-mono text-xs font-bold text-slate-900">RES-2026-00417</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[10px] font-black tracking-wider text-[#073823] shadow-2xs">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#073823] animate-pulse"></span>
                            UNDER REVIEW
                        </span>
                    </div>

                    <!-- Research Title & Details -->
                    <div class="py-5 space-y-4">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Research Topic</span>
                            <h3 class="mt-1 text-base sm:text-lg font-black leading-snug text-slate-900">
                                Development of a University Research Management &amp; Archiving Monolith
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-3.5 border border-slate-200/80">
                            <div>
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Lead Researcher</span>
                                <p class="mt-0.5 text-xs font-bold text-slate-800">Capstone Group 1</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Department</span>
                                <p class="mt-0.5 text-xs font-bold text-slate-800">College of Engineering</p>
                            </div>
                        </div>
                    </div>

                    <!-- Live 5-Stage Stepper strictly in Emerald and Gold -->
                    <div class="border-t border-slate-100 pt-4 space-y-2">
                        <div class="flex items-center justify-between text-[10px] font-bold text-slate-500 mb-1">
                            <span>Stage 3 of 5</span>
                            <span class="text-[#073823] font-black">Proposal Defense Scheduled</span>
                        </div>

                        <div class="relative flex justify-between items-center before:absolute before:left-[10%] before:right-[10%] before:top-3.5 before:h-1 before:bg-slate-200">
                            <!-- Stage 1 -->
                            <div class="relative z-10 flex flex-col items-center">
                                <span class="w-7 h-7 rounded-full bg-[#073823] text-white flex items-center justify-center text-xs shadow-sm">
                                    <i class="ph ph-check-bold"></i>
                                </span>
                                <span class="text-[8px] font-bold text-slate-600 mt-1">Proposal</span>
                            </div>

                            <!-- Stage 2 -->
                            <div class="relative z-10 flex flex-col items-center">
                                <span class="w-7 h-7 rounded-full bg-[#073823] text-white flex items-center justify-center text-xs shadow-sm">
                                    <i class="ph ph-check-bold"></i>
                                </span>
                                <span class="text-[8px] font-bold text-slate-600 mt-1">Review</span>
                            </div>

                            <!-- Stage 3: Current -->
                            <div class="relative z-10 flex flex-col items-center">
                                <span class="w-7 h-7 rounded-full bg-[#eebc3f] text-[#073823] flex items-center justify-center text-xs ring-4 ring-amber-400/30 shadow-md font-black animate-pulse">
                                    <i class="ph ph-presentation"></i>
                                </span>
                                <span class="text-[8px] font-black text-[#073823] mt-1">Defense</span>
                            </div>

                            <!-- Stage 4 -->
                            <div class="relative z-10 flex flex-col items-center">
                                <span class="w-7 h-7 rounded-full bg-slate-100 border border-slate-300 text-slate-400 flex items-center justify-center text-xs">
                                    <i class="ph ph-seal-check"></i>
                                </span>
                                <span class="text-[8px] font-medium text-slate-400 mt-1">Revision</span>
                            </div>

                            <!-- Stage 5 -->
                            <div class="relative z-10 flex flex-col items-center">
                                <span class="w-7 h-7 rounded-full bg-slate-100 border border-slate-300 text-slate-400 flex items-center justify-center text-xs">
                                    <i class="ph ph-archive"></i>
                                </span>
                                <span class="text-[8px] font-medium text-slate-400 mt-1">Archiving</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Scroll Down Point Arrow Indicator -->
        <div class="relative z-10 w-full pb-6 pt-2 flex flex-col items-center justify-center">
            <a
                href="#lifecycle"
                class="group flex flex-col items-center gap-1.5 text-xs font-bold text-emerald-200/90 hover:text-[#eebc3f] transition-all cursor-pointer select-none"
                aria-label="Scroll down to explore research workflow"
            >
                <span class="text-[10px] font-black uppercase tracking-[0.25em] text-[#eebc3f]/90 group-hover:text-[#eebc3f] transition-colors">Explore Workflow</span>
                <div class="w-8 h-8 rounded-full border border-[#eebc3f]/40 bg-black/20 group-hover:border-[#eebc3f] group-hover:bg-[#eebc3f]/20 flex items-center justify-center text-sm text-[#eebc3f] transition-all shadow-sm animate-bounce">
                    <i class="ph ph-caret-down-bold text-base"></i>
                </div>
            </a>
        </div>
    </section>

    <!-- RESEARCH LIFECYCLE SECTION: How Research Moves (Visible upon scrolling) -->
    <section id="lifecycle" class="relative bg-[#073823] border-t border-emerald-700/30 py-20 text-white sm:py-24 overflow-hidden">
        <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-black/25 border border-[#eebc3f]/40 text-[10px] font-black uppercase tracking-[0.2em] text-[#eebc3f]">
                    <i class="ph ph-git-fork text-sm"></i>
                    <span>Systematic Research Workflow</span>
                </div>
                <h2 class="text-3xl font-black tracking-tight text-white sm:text-4xl">
                    How Research Moves
                </h2>
                <p class="text-sm sm:text-base text-emerald-100/80">
                    A transparent, five-phase progression ensuring quality, rigor, and institutional archiving.
                </p>
            </div>

            <div class="relative mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
                @php
                    $steps = [
                        ['icon' => 'ph-file-text', 'title' => 'Title & Proposal', 'desc' => 'Initial topic formulation, group formation, and facilitator screening.'],
                        ['icon' => 'ph-user-check', 'title' => 'Adviser Review', 'desc' => 'Iterative manuscript consultation, revision tracking, and endorsement.'],
                        ['icon' => 'ph-presentation', 'title' => 'Proposal Defense', 'desc' => 'Oral defense scheduling, panel deliberation, and rubric evaluation.'],
                        ['icon' => 'ph-seal-check', 'title' => 'Final Defense', 'desc' => 'Final manuscript evaluation, dean sign-off, and routing slip approval.'],
                        ['icon' => 'ph-archive', 'title' => 'Institutional Archive', 'desc' => 'Secured private repository storage and university research cataloging.']
                    ];
                @endphp

                @foreach($steps as $index => $step)
                    <article class="group relative flex flex-col items-center rounded-3xl border border-emerald-600/40 bg-white/5 p-6 text-center transition-all duration-300 hover:-translate-y-2 hover:border-[#eebc3f]/60 hover:bg-white/10 shadow-lg shadow-black/10">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#042416] border border-[#eebc3f]/50 text-sm font-black text-[#eebc3f] shadow-md group-hover:scale-110 transition-transform">
                            0{{ $index + 1 }}
                        </span>
                        <i class="ph {{ $step['icon'] }} mt-4 text-3xl text-[#eebc3f] group-hover:scale-110 transition-transform"></i>
                        <h3 class="mt-3 text-base font-black text-white">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-xs leading-relaxed text-emerald-100/75">{{ $step['desc'] }}</p>
                    </article>
                @endforeach
            </div>

            <!-- Capability Feature Badges Strip strictly in Emerald & Gold -->
            <div id="compliance" class="mt-16 flex flex-wrap items-center justify-center gap-3 rounded-2xl border border-emerald-600/40 bg-black/20 px-6 py-4.5 text-xs font-bold text-emerald-100 shadow-sm sm:gap-8">
                <span class="inline-flex items-center gap-2">
                    <i class="ph ph-shield-check text-base text-[#eebc3f]"></i>
                    <span>Encrypted Manuscript Vault</span>
                </span>
                <span class="hidden h-4 w-px bg-emerald-600/40 sm:block"></span>

                <span class="inline-flex items-center gap-2">
                    <i class="ph ph-users-three text-base text-[#eebc3f]"></i>
                    <span>Multi-Role Academic RBAC</span>
                </span>
                <span class="hidden h-4 w-px bg-emerald-600/40 sm:block"></span>

                <span class="inline-flex items-center gap-2">
                    <i class="ph ph-signature text-base text-[#eebc3f]"></i>
                    <span>Digital E-Signatures</span>
                </span>
                <span class="hidden h-4 w-px bg-emerald-600/40 sm:block"></span>

                <span class="inline-flex items-center gap-2">
                    <i class="ph ph-arrows-counter-clockwise text-base text-[#eebc3f]"></i>
                    <span>Comprehensive Audit Trail</span>
                </span>
            </div>
        </div>
    </section>
</main>

<!-- Global University Footer -->
<footer class="border-t border-emerald-700/30 bg-[#073823] px-6 py-12 text-white/80">
    <div class="mx-auto max-w-7xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-3.5">
            <img src="{{ asset('images/ndmu_logo.png') }}" alt="NDMU Logo" class="h-12 w-auto object-contain">
            <div class="flex flex-col leading-none border-l border-white/20 pl-3">
                <span class="font-heading font-black text-lg text-white">Notre Dame of Marbel University</span>
                <span class="text-[10px] font-bold text-[#eebc3f] tracking-wider uppercase mt-0.5">Research Management &amp; Archiving System</span>
            </div>
        </div>

        <div class="text-center md:text-right space-y-1 text-xs text-white/70">
            <p class="font-bold text-white/90">Marist Brothers of the Schools &bull; Academic Excellence</p>
            <p>&copy; {{ now()->year }} Notre Dame of Marbel University. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
    const siteHeader = document.querySelector('[data-site-header]');
    const updateHeader = () => siteHeader?.classList.toggle('is-scrolled', window.scrollY > 15);
    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();
</script>
</body>
</html>
