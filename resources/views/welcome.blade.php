<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Notre Dame of Marbel University Research Management and Archiving System (NDMU-RMAS). Centralizing university research proposals, reviews, defenses, and institutional archiving.">
    <title>NDMU-RMAS - Notre Dame of Marbel University Research Management System</title>
    <x-favicon />
    
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

        @keyframes floatCard {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(0.35deg); }
        }
        @keyframes floatBadge1 {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            50% { transform: translateY(-7px) translateX(3px); }
        }
        @keyframes floatBadge2 {
            0%, 100% { transform: translateY(0px) translateX(0px); }
            50% { transform: translateY(6px) translateX(-3px); }
        }
        @keyframes pulseHalo {
            0%, 100% { opacity: 0.45; transform: scale(0.98); }
            50% { opacity: 0.8; transform: scale(1.04); }
        }
        @keyframes shimmerBar {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        @keyframes radarRipple {
            0% { transform: scale(0.9); opacity: 0.8; }
            70% { transform: scale(1.8); opacity: 0; }
            100% { transform: scale(2); opacity: 0; }
        }
        .animate-card-float { animation: floatCard 7s ease-in-out infinite; }
        .animate-badge-float-1 { animation: floatBadge1 5.5s ease-in-out infinite; }
        .animate-badge-float-2 { animation: floatBadge2 6.5s ease-in-out infinite; }
        .animate-pulse-halo { animation: pulseHalo 4.5s ease-in-out infinite; }
        .animate-shimmer-bar { background-size: 200% 100%; animation: shimmerBar 3.5s linear infinite; }
        .animate-radar-ripple { animation: radarRipple 2.2s cubic-bezier(0, 0.2, 0.8, 1) infinite; }

        .landing-hero {
            min-height: calc(100svh - 76px);
        }

        /* The hero remains stacked below Tailwind's lg breakpoint. This also
           covers mobile browsers using "Desktop site" (usually a ~980px viewport). */
        @media (max-width: 1023px) {
            .landing-hero {
                min-height: 0;
            }

            .landing-hero-content {
                grid-template-columns: minmax(0, 1fr);
                margin-block: 0;
            }

            .landing-hero-content > * {
                min-width: 0;
                max-width: 100%;
            }

            .landing-showcase {
                width: 100%;
            }

            .landing-hero-content h1,
            .landing-hero-content p {
                overflow-wrap: anywhere;
            }

            .landing-showcase .animate-badge-float-1,
            .landing-showcase .animate-badge-float-2 {
                max-width: calc(100% - 1rem);
            }

            .landing-showcase .animate-card-float,
            .landing-showcase .animate-badge-float-1,
            .landing-showcase .animate-badge-float-2,
            .landing-showcase .animate-pulse-halo {
                animation: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .animate-card-float,
            .animate-badge-float-1,
            .animate-badge-float-2,
            .animate-pulse-halo,
            .animate-shimmer-bar,
            .animate-radar-ripple {
                animation: none;
            }
        }
    </style>
</head>
<body class="m-0 overflow-x-hidden bg-[#073823] text-white antialiased selection:bg-[#eebc3f] selection:text-[#073823]">

<!-- Global Sticky University Header -->
<header data-site-header class="site-header sticky top-0 z-50 bg-white border-b border-slate-200/80 transition-all duration-300">
    <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-2 px-3 sm:min-h-[76px] sm:px-6 lg:px-8">
        <!-- System Brand Logo -->
        <a href="{{ route('home') }}" class="group flex min-w-0 items-center gap-2.5 transition-transform duration-200 hover:scale-[1.01] sm:gap-3.5" aria-label="NDMU Research Management System">
            <x-ndmu-n-logo size="md" :showGlow="false" />
            <div class="flex min-w-0 flex-col border-l-2 border-[#eebc3f] pl-2.5 leading-none sm:pl-3">
                <div class="flex items-center gap-2">
                    <span class="font-heading text-base font-black tracking-tight text-[#073823] sm:text-xl">NDMU</span>
                    <span class="px-2 py-0.5 rounded-full bg-[#eebc3f]/20 border border-[#eebc3f]/50 text-[#073823] font-black text-[9px] uppercase tracking-wider hidden sm:inline-block">RMAS</span>
                </div>
                <span class="mt-0.5 whitespace-nowrap text-[7px] font-black uppercase tracking-[0.14em] text-[#0e5c3a] min-[390px]:text-[8px] sm:text-[10px] sm:tracking-[0.22em]">Research Management</span>
            </div>
        </a>

        <!-- Auth Action Buttons -->
        <div class="flex shrink-0 items-center gap-1 sm:gap-3">
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-[#073823] hover:bg-[#0e5c3a] px-4 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-950/20 transition duration-200 hover:-translate-y-0.5"
                >
                    <i class="ph ph-squares-four text-sm text-[#eebc3f]"></i>
                    <span>Dashboard</span>
                </a>
            @endauth
            @if(Route::has('login'))
                <a
                    href="{{ route('login') }}"
                    class="px-2 py-2.5 text-[11px] font-black text-[#073823] transition duration-200 hover:text-[#0e5c3a] sm:px-4 sm:text-xs"
                >
                    Log in
                </a>
            @endif
            @if(Route::has('register'))
                <a
                    href="{{ route('register') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-[#eebc3f] to-[#f4c542] px-3 py-2.5 text-[11px] font-black text-[#073823] shadow-md shadow-amber-950/15 transition duration-200 hover:-translate-y-0.5 hover:brightness-105 sm:gap-2 sm:rounded-2xl sm:px-4.5 sm:text-xs"
                >
                    <i class="ph ph-user-plus text-sm"></i>
                    <span>Register</span>
                </a>
            @endif
        </div>
    </div>
</header>

<main class="bg-[#073823]">
    <!-- HERO SECTION: full-height on desktop, natural-height while stacked -->
    <section class="landing-hero relative flex flex-col justify-between overflow-hidden bg-[#073823] text-white">
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
        <div class="landing-hero-content relative z-10 mx-auto grid w-full min-w-0 max-w-7xl grid-cols-[minmax(0,1fr)] items-center gap-8 px-4 pb-10 pt-8 sm:my-auto sm:gap-10 sm:px-6 sm:py-12 lg:grid-cols-12 lg:px-8">
            
            <!-- Left Hero Content -->
            <div class="relative z-10 min-w-0 max-w-full space-y-4 sm:space-y-6 lg:col-span-7">
                <!-- Eyebrow Pill -->
                <div class="inline-flex max-w-full items-center gap-2 rounded-full border border-[#eebc3f]/40 bg-black/25 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.12em] text-[#eebc3f] shadow-sm sm:gap-2.5 sm:px-4 sm:py-2 sm:text-xs sm:tracking-[0.18em]">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#eebc3f] opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-[#eebc3f]"></span>
                    </span>
                    <span>NDMU Institutional Research Portal</span>
                </div>

                <!-- Main Hero Headline -->
                <h1 class="max-w-full break-words text-[2rem] font-black leading-[1.12] tracking-tight text-white min-[430px]:text-4xl sm:text-5xl lg:text-[3.5rem]">
                    Manage research from 
                    <span class="text-[#eebc3f]">proposal</span> 
                    to institutional 
                    <span class="text-[#eebc3f]">archiving.</span>
                </h1>

                <!-- Supporting Description -->
                <p class="max-w-full break-words text-[13px] font-medium leading-relaxed text-emerald-100/90 sm:max-w-xl sm:text-base">
                    A university-grade research ecosystem connecting student researchers, faculty advisers, review panelists, facilitators, and academic deans in one seamless platform.
                </p>

                <!-- Action CTA Buttons -->
                <div class="flex min-w-0 flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:gap-3.5">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="group inline-flex min-h-12 w-full min-w-0 items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#eebc3f] to-[#f4c542] px-5 text-center text-xs font-black text-[#073823] shadow-xl shadow-black/20 transition duration-200 hover:-translate-y-0.5 hover:brightness-105 sm:min-h-[52px] sm:w-auto sm:rounded-2xl sm:px-8 sm:text-sm"
                        >
                            <span>Open Your Workspace</span>
                            <i class="ph ph-arrow-right text-base font-bold transition-transform duration-200 group-hover:translate-x-1"></i>
                        </a>
                    @else
                        @if(Route::has('login'))
                            <a
                                href="{{ route('login') }}"
                                class="group inline-flex min-h-12 w-full min-w-0 items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#eebc3f] to-[#f4c542] px-5 text-center text-xs font-black text-[#073823] shadow-xl shadow-black/20 transition duration-200 hover:-translate-y-0.5 hover:brightness-105 sm:min-h-[52px] sm:w-auto sm:rounded-2xl sm:px-8 sm:text-sm"
                            >
                                <span>Sign In to Continue</span>
                                <i class="ph ph-arrow-right text-base font-bold transition-transform duration-200 group-hover:translate-x-1"></i>
                            </a>
                        @endif
                        @if(Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex min-h-12 w-full min-w-0 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-5 text-center text-xs font-bold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-white/20 sm:min-h-[52px] sm:w-auto sm:rounded-2xl sm:px-7 sm:text-sm"
                            >
                                <i class="ph ph-student text-base text-[#eebc3f]"></i>
                                <span>Register Student Account</span>
                            </a>
                        @endif
                    @endauth
                </div>

                <!-- 3 Metric Highlights Strip -->
                <div class="grid w-full min-w-0 max-w-lg grid-cols-3 gap-2 overflow-hidden border-t border-white/15 pt-4 sm:gap-4 sm:pt-6">
                    <div class="min-w-0">
                        <span class="block text-2xl sm:text-3xl font-black text-[#eebc3f]">25</span>
                        <span class="mt-0.5 block text-[9px] font-bold uppercase tracking-wide text-emerald-100/80 sm:text-[11px] sm:tracking-wider">Official Forms</span>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-2xl sm:text-3xl font-black text-[#eebc3f]">14</span>
                        <span class="mt-0.5 block text-[9px] font-bold uppercase tracking-wide text-emerald-100/80 sm:text-[11px] sm:tracking-wider">Journey Stages</span>
                    </div>
                    <div class="min-w-0">
                        <span class="block text-2xl sm:text-3xl font-black text-[#eebc3f]">100%</span>
                        <span class="mt-0.5 block text-[9px] font-bold uppercase tracking-wide text-emerald-100/80 sm:text-[11px] sm:tracking-wider">Secure Vault</span>
                    </div>
                </div>
            </div>

            <!-- Right Interactive Live Record Showcase Card -->
            <div class="landing-showcase relative mx-auto mt-2 w-full min-w-0 max-w-[500px] px-2 sm:mt-0 sm:px-0 lg:col-span-5 lg:justify-self-end">
                <!-- Ambient Multi-Color Animated Halo Glow Behind Card -->
                <div class="animate-pulse-halo pointer-events-none absolute -inset-5 rounded-[2.5rem] bg-gradient-to-tr from-emerald-500/30 via-[#eebc3f]/25 to-teal-400/25 blur-3xl"></div>

                <!-- Floating Satellite Badge 1: Top-Right (Panel Evaluation Scheduled) -->
                <div class="animate-badge-float-1 absolute right-0 -top-4 z-20 flex max-w-[calc(100%-1rem)] items-center gap-2 rounded-xl border border-white/20 bg-[#073823]/95 px-2.5 py-2 text-white shadow-2xl shadow-black/50 backdrop-blur-xl sm:-right-5 sm:-top-5 sm:gap-3 sm:rounded-2xl sm:px-3.5 sm:py-2.5">
                    <div class="flex -space-x-2 overflow-hidden shrink-0">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#eebc3f] text-[9px] font-black text-[#073823] ring-2 ring-[#073823]">JD</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-[9px] font-black text-white ring-2 ring-[#073823]">MB</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-teal-400 text-[9px] font-black text-[#073823] ring-2 ring-[#073823]">RC</span>
                    </div>
                    <div class="leading-tight">
                        <div class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                            <span class="text-[9px] font-black tracking-wider text-emerald-300 uppercase">3 Panelists</span>
                        </div>
                        <p class="text-[11px] font-bold text-white">Oral Defense Ready</p>
                    </div>
                </div>

                <!-- Floating Satellite Badge 2: Bottom-Left (Official Endorsement) -->
                <div class="animate-badge-float-2 absolute -bottom-3 left-0 z-20 flex max-w-[calc(100%-1rem)] items-center gap-2 rounded-xl border border-emerald-500/20 bg-white/95 px-2.5 py-1.5 text-slate-800 shadow-2xl shadow-black/30 backdrop-blur-xl sm:-bottom-4 sm:-left-5 sm:gap-2.5 sm:rounded-2xl sm:px-3.5 sm:py-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-gradient-to-br from-[#eebc3f] to-[#d4a027] text-[#073823] text-sm font-bold shadow-2xs shrink-0">
                        <i class="ph ph-shield-check"></i>
                    </span>
                    <div class="leading-tight">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">RES-033 Endorsement</p>
                        <p class="text-[11px] font-black text-[#073823]">Faculty Signed &amp; Approved</p>
                    </div>
                </div>

                <!-- Main Floating Showcase Card Container -->
                <div class="animate-card-float group relative min-w-0 max-w-full overflow-hidden rounded-3xl border border-white/70 bg-white/95 p-4 text-slate-800 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.5)] backdrop-blur-md transition-all hover:shadow-[0_30px_70px_-15px_rgba(0,0,0,0.6)] sm:rounded-[2rem] sm:p-7">
                    <!-- Shimmering Top Accent Line -->
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] via-[#0e5c3a] to-[#073823] animate-shimmer-bar"></div>
                    <!-- Subtle Golden Ambient Radial Sheen in Top-Right Corner -->
                    <div class="pointer-events-none absolute -top-16 -right-16 h-36 w-36 rounded-full bg-[#eebc3f]/15 blur-2xl"></div>

                    <!-- Card Header Row -->
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <x-ndmu-n-logo size="sm" :showGlow="false" />
                                <span class="absolute -bottom-0.5 -right-0.5 flex h-2.5 w-2.5">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-[#0e5c3a] ring-2 ring-white"></span>
                                </span>
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <p class="text-[9px] font-black uppercase tracking-widest text-[#073823]">Active Manuscript</p>
                                    <span class="inline-flex items-center text-[9px] text-[#0e5c3a]">
                                        <i class="ph ph-check-circle-fill"></i>
                                    </span>
                                </div>
                                <p class="font-mono text-xs font-bold tracking-tight text-slate-900 flex items-center gap-1 mt-0.5">
                                    <span>RES-2026-00417</span>
                                    <span class="rounded bg-slate-100 px-1 py-0.5 text-[8px] font-mono font-bold text-slate-500 uppercase">v2.1</span>
                                </p>
                            </div>
                        </div>

                        <!-- Live Status Pill with Radar Ripple -->
                        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200/80 bg-emerald-50/90 px-3 py-1 text-[10px] font-black tracking-wider text-[#073823] shadow-2xs shrink-0">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-radar-ripple rounded-full bg-emerald-500"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-[#0e5c3a]"></span>
                            </span>
                            <span>UNDER REVIEW</span>
                        </span>
                    </div>

                    <!-- Research Title & Details -->
                    <div class="py-4 space-y-3.5">
                        <!-- Category Tags -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-emerald-50 border border-emerald-200/60 text-[#073823] font-black text-[9px] uppercase tracking-wider">
                                <i class="ph ph-graduation-cap"></i>
                                <span>Capstone Research</span>
                            </span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-amber-50 border border-amber-200/60 text-amber-900 font-black text-[9px] uppercase tracking-wider">
                                <span>BSIT Program</span>
                            </span>
                        </div>

                        <!-- Research Title -->
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Canonical Research Topic</span>
                            <h3 class="mt-1 text-base sm:text-lg font-black leading-snug text-slate-900 group-hover:text-[#073823] transition-colors">
                                Development of a University Research Management &amp; Archiving Monolith
                            </h3>
                        </div>

                        <!-- Enhanced Metadata 2-Column Grid -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/60 p-3 border border-slate-200/70 hover:border-emerald-300/60 transition-colors">
                                <div class="flex items-center gap-1.5 text-slate-400 text-[9px] font-bold uppercase tracking-wider">
                                    <i class="ph ph-users-three text-xs text-[#0e5c3a]"></i>
                                    <span>Lead Researcher</span>
                                </div>
                                <p class="mt-1 text-xs font-bold text-slate-900 truncate">Capstone Group 1</p>
                                <span class="text-[10px] font-medium text-slate-500 block">3 Student Co-Authors</span>
                            </div>

                            <div class="rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/60 p-3 border border-slate-200/70 hover:border-emerald-300/60 transition-colors">
                                <div class="flex items-center gap-1.5 text-slate-400 text-[9px] font-bold uppercase tracking-wider">
                                    <i class="ph ph-buildings text-xs text-[#0e5c3a]"></i>
                                    <span>Department &amp; Adviser</span>
                                </div>
                                <p class="mt-1 text-xs font-bold text-slate-900 truncate">College of Engineering</p>
                                <span class="text-[10px] font-medium text-slate-500 block truncate">Adv: Engr. M. Dollaga</span>
                            </div>
                        </div>

                        <!-- Live Scheduled Defense Information Bar -->
                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-[#073823]/[0.03] border border-[#073823]/10 px-3.5 py-2.5">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-[#073823] text-[#eebc3f] text-xs font-bold shadow-2xs shrink-0">
                                    <i class="ph ph-calendar-check"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Oral Defense Session</p>
                                    <p class="text-xs font-bold text-slate-800 truncate">Conference Room A · 10:00 AM – 12:00 PM</p>
                                </div>
                            </div>
                            <span class="rounded-full bg-[#eebc3f]/20 border border-[#eebc3f]/60 px-2 py-0.5 text-[9px] font-black uppercase text-[#073823] shrink-0">
                                Scheduled
                            </span>
                        </div>
                    </div>

                    <!-- Live 5-Stage Stepper with Vibrant Dual Track & Active Radar Ripple -->
                    <div class="border-t border-slate-100 pt-4 space-y-2.5">
                        <div class="flex items-center justify-between text-[10px] font-bold text-slate-500">
                            <span class="flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-[#0e5c3a]"></span>
                                <span>Milestone 3 of 5</span>
                            </span>
                            <span class="inline-flex items-center gap-1 font-black text-[#073823]">
                                <i class="ph ph-broadcast text-xs text-emerald-600 animate-pulse"></i>
                                <span>Proposal Defense Scheduled</span>
                            </span>
                        </div>

                        <!-- Stepper with dual-progress track -->
                        <div class="relative flex justify-between items-center py-1">
                            <!-- Background Completed Track (Stages 1 to 3) -->
                            <div class="absolute left-[10%] w-[45%] top-4 h-1 bg-gradient-to-r from-[#073823] via-[#0e5c3a] to-[#eebc3f] rounded-full z-0"></div>
                            <!-- Background Remaining Track (Stages 3 to 5) -->
                            <div class="absolute right-[10%] w-[45%] top-4 h-1 bg-slate-200 rounded-full z-0"></div>

                            <!-- Stage 1 -->
                            <div class="relative z-10 flex flex-col items-center group/node">
                                <span class="w-8 h-8 rounded-full bg-[#073823] text-[#eebc3f] flex items-center justify-center text-xs shadow-md shadow-emerald-950/20 ring-2 ring-white transition-transform duration-200 group-hover/node:scale-110">
                                    <i class="ph ph-check-bold"></i>
                                </span>
                                <span class="text-[8px] font-bold text-slate-700 mt-1.5">Proposal</span>
                            </div>

                            <!-- Stage 2 -->
                            <div class="relative z-10 flex flex-col items-center group/node">
                                <span class="w-8 h-8 rounded-full bg-[#073823] text-[#eebc3f] flex items-center justify-center text-xs shadow-md shadow-emerald-950/20 ring-2 ring-white transition-transform duration-200 group-hover/node:scale-110">
                                    <i class="ph ph-check-bold"></i>
                                </span>
                                <span class="text-[8px] font-bold text-slate-700 mt-1.5">Review</span>
                            </div>

                            <!-- Stage 3: Current with glowing ripple ring -->
                            <div class="relative z-10 flex flex-col items-center">
                                <div class="relative flex items-center justify-center">
                                    <span class="absolute h-10 w-10 rounded-full bg-[#eebc3f] opacity-40 animate-ping"></span>
                                    <span class="relative w-8 h-8 rounded-full bg-gradient-to-br from-[#eebc3f] to-[#f4c542] text-[#073823] flex items-center justify-center text-xs ring-4 ring-amber-400/40 shadow-lg shadow-amber-500/30 font-black">
                                        <i class="ph ph-presentation font-bold"></i>
                                    </span>
                                </div>
                                <span class="text-[8px] font-black text-[#073823] mt-1.5">Defense</span>
                            </div>

                            <!-- Stage 4 -->
                            <div class="relative z-10 flex flex-col items-center group/node">
                                <span class="w-8 h-8 rounded-full bg-white border-2 border-slate-300 text-slate-400 flex items-center justify-center text-xs shadow-2xs ring-2 ring-white transition-transform duration-200 group-hover/node:scale-110">
                                    <i class="ph ph-seal-check"></i>
                                </span>
                                <span class="text-[8px] font-medium text-slate-400 mt-1.5">Revision</span>
                            </div>

                            <!-- Stage 5 -->
                            <div class="relative z-10 flex flex-col items-center group/node">
                                <span class="w-8 h-8 rounded-full bg-white border-2 border-slate-300 text-slate-400 flex items-center justify-center text-xs shadow-2xs ring-2 ring-white transition-transform duration-200 group-hover/node:scale-110">
                                    <i class="ph ph-archive"></i>
                                </span>
                                <span class="text-[8px] font-medium text-slate-400 mt-1.5">Archiving</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Scroll Down Point Arrow Indicator -->
        <div class="relative z-10 flex w-full flex-col items-center justify-center pb-5 pt-1 sm:pb-6 sm:pt-2">
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

<!-- Landing Page Footer -->
<footer class="bg-[#073823] px-5 py-1 text-center text-xs text-white">
    <p>&copy; {{ now()->year }} Notre Dame of Marbel University. All rights reserved.</p>
</footer>

<script>
    const siteHeader = document.querySelector('[data-site-header]');
    const updateHeader = () => siteHeader?.classList.toggle('is-scrolled', window.scrollY > 15);
    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();
</script>
</body>
</html>
