<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="NDMU Research Management System for submissions, reviews, defenses, revisions, and archiving.">
    <title>NDMU Research Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .hero-pattern {
            background-color: #003D29;
            background-image: 
                radial-gradient(circle at 85% 15%, rgba(229, 183, 46, 0.06) 0%, transparent 45%),
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 100% 100%, 36px 36px, 36px 36px;
        }
        .site-header { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        .site-header.is-scrolled {
            background-color: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px -10px rgba(0, 61, 41, 0.08);
        }
        .paper-lines {
            background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 27px, rgba(0, 61, 41, 0.04) 28px);
        }
    </style>
</head>
<body class="m-0 overflow-x-hidden bg-[#F7FAF8] text-[#0F291E] antialiased selection:bg-[#E5B72E] selection:text-[#003D29]">

<!-- Header -->
<header data-site-header class="site-header sticky top-0 z-50 border-b border-[#003D29]/10 bg-white/95 backdrop-blur-md">
    <div class="mx-auto flex min-h-[76px] max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="group flex items-center gap-3 transition-transform duration-200 hover:scale-[1.01]" aria-label="NDMU Research Management home">
            <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="Notre Dame of Marbel University seal" width="48" height="48" class="h-11 w-11 shrink-0 object-contain sm:h-12 sm:w-12 transition-transform duration-300 group-hover:rotate-3">
            <span class="border-l-2 border-[#E5B72E] pl-3 leading-tight">
                <strong class="block text-base font-black tracking-tight text-[#003D29] sm:text-lg">NDMU</strong>
                <span class="block text-[9px] font-extrabold uppercase tracking-[0.18em] text-[#00633E] sm:text-[10px]">Research Management System</span>
            </span>
        </a>
    </div>
</header>

<main>
<!-- Hero Section -->
<section class="hero-pattern relative overflow-hidden text-white">
    <!-- Subtle background accent graphics -->
    <div class="pointer-events-none absolute -right-24 -top-24 h-[420px] w-[420px] rounded-full border-[60px] border-white/5 blur-xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-32 h-[500px] w-[500px] rounded-full border-[70px] border-[#E5B72E]/10 blur-2xl"></div>

    <div class="relative mx-auto grid min-h-[640px] max-w-7xl items-center gap-12 px-6 py-16 lg:grid-cols-12 lg:px-8 lg:py-24">
        
        <!-- Left Hero Column -->
        <div class="relative z-10 lg:col-span-7">
            <div class="mb-6 inline-flex items-center gap-2.5 rounded-full border border-[#E5B72E]/30 bg-[#002E1F]/90 px-4 py-2 text-xs font-bold uppercase tracking-widest text-[#E5B72E] backdrop-blur-md shadow-inner">
                <span class="relative flex h-2 w-2">
                  <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#E5B72E] opacity-75"></span>
                  <span class="relative inline-flex h-2 w-2 rounded-full bg-[#E5B72E]"></span>
                </span>
                Internal · NDMU Research Management
            </div>

            <h1 class="text-4xl font-black leading-[1.08] tracking-tight sm:text-5xl lg:text-[3.6rem]">
                Manage research from <span class="text-[#E5B72E]">proposal</span> to institutional <span class="text-[#E5B72E]">archiving.</span>
            </h1>

            <p class="mt-6 max-w-xl text-base leading-relaxed text-white/85 sm:text-lg">
                NDMU Research Management System centralizes research submissions, reviews, approvals, defenses, revisions, and institutional archiving in one secure platform.
            </p>

            <div class="mt-9 flex flex-col gap-3.5 sm:flex-row sm:items-center">
                @if(Route::has('login'))
                    <a href="{{ route('login') }}" class="group inline-flex min-h-[52px] items-center justify-center gap-2.5 rounded-xl bg-[#E5B72E] hover:bg-[#d4a325] px-8 text-sm font-extrabold text-[#002E1F] shadow-lg shadow-[#E5B72E]/20 transition duration-200 hover:shadow-xl hover:-translate-y-0.5">
                        Log in to Continue 
                        <i class="ph-bold ph-arrow-right text-base transition-transform duration-200 group-hover:translate-x-1"></i>
                    </a>
                @endif
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-8 text-sm font-bold text-white backdrop-blur-md transition duration-200 hover:border-white/40 hover:bg-white/20 hover:-translate-y-0.5">
                        Create an Account
                    </a>
                @endif
            </div>

            <!-- Stats strip -->
            <div class="mt-12 grid grid-cols-3 gap-4 border-t border-white/10 pt-8">
                <div>
                    <span class="block text-2xl font-black text-[#E5B72E] sm:text-3xl">25</span>
                    <span class="mt-0.5 block text-xs font-semibold text-white/75">Official Forms</span>
                </div>
                <div>
                    <span class="block text-2xl font-black text-[#E5B72E] sm:text-3xl">13</span>
                    <span class="mt-0.5 block text-xs font-semibold text-white/75">Journey Stages</span>
                </div>
                <div>
                    <span class="block text-2xl font-black text-[#E5B72E] sm:text-3xl">100%</span>
                    <span class="mt-0.5 block text-xs font-semibold text-white/75">Secure Access</span>
                </div>
            </div>
        </div>

        <!-- Right Preview Card Column -->
        <div class="relative lg:col-span-5 lg:justify-self-end w-full max-w-[480px]" aria-label="Sample research record under review">
            <!-- Floating backdrop cards -->
            <div class="absolute inset-x-5 bottom-0 top-10 rotate-3 rounded-3xl border border-white/15 bg-[#002E1F] shadow-2xl opacity-60"></div>

            <!-- Main paper card -->
            <article class="paper-lines relative rounded-3xl border border-[#CFE3D8] bg-white p-6 text-[#0F291E] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.3)] sm:p-7">
                <div class="flex items-center justify-between gap-3 border-b border-[#003D29]/10 pb-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-[#00633E]">Research Record</p>
                        <p class="mt-0.5 font-mono text-xs font-bold text-[#003D29] sm:text-sm">RES-2026-00417</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-[#00633E]/20 bg-[#EAF5EF] px-3 py-1 text-[10px] font-black tracking-wider text-[#00633E] shadow-sm">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#00633E]"></span>
                        UNDER REVIEW
                    </span>
                </div>

                <div class="py-5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#526359]">Research title</p>
                    <h2 class="mt-1.5 text-lg font-black leading-snug text-[#003D29] sm:text-xl">
                        Development of a Research Management Information System
                    </h2>
                    <div class="mt-5 grid grid-cols-2 gap-4 rounded-xl bg-[#F7FAF8] p-3.5 border border-[#003D29]/10">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-wider text-[#526359]">Submitted by</p>
                            <p class="mt-0.5 text-xs font-bold text-[#003D29]">Research Group</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-wider text-[#526359]">College / Program</p>
                            <p class="mt-0.5 text-xs font-bold text-[#003D29]">NDMU Academic Unit</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-[#003D29]/10 pt-4">
                    <div class="relative flex justify-between before:absolute before:left-[8%] before:right-[8%] before:top-3 before:h-0.5 before:bg-[#DDE5E1]">
                        @foreach([['check','Title Proposal','done'],['user-check','Adviser Review','done'],['presentation-chart','Proposal Defense','current'],['seal-check','Final Defense','next'],['archive','Archiving','next']] as $stage)
                        <div class="relative z-10 flex flex-col items-center text-center">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full border text-[11px] transition-transform duration-200 hover:scale-110 {{ $stage[2]==='done'?'border-[#003D29] bg-[#003D29] text-white':($stage[2]==='current'?'border-[#E5B72E] bg-[#E5B72E] text-[#002E1F] ring-4 ring-[#E5B72E]/20 shadow-sm':'border-[#DDE5E1] bg-[#F7FAF8] text-[#68766F]') }}">
                                <i class="ph-bold ph-{{ $stage[0] }}"></i>
                            </span>
                            <span class="mt-1.5 hidden text-[8px] font-bold leading-tight text-[#526359] sm:block">{{ $stage[1] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </article>
        </div>

    </div>
</section>

<!-- Research Lifecycle Section -->
<section class="bg-[#002E1F] relative border-t border-white/10 py-20 text-white sm:py-24">
    <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest text-[#E5B72E]">
                <i class="ph-bold ph-path text-base" aria-hidden="true"></i>Research lifecycle
            </p>
            <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                How research moves
            </h2>
            <p class="mt-2 text-base text-white/80">
                One organized process from proposal to institutional archiving.
            </p>
        </div>

        <div class="relative mt-12 grid gap-6 md:grid-cols-5 md:gap-4">
            @foreach([
                ['file-text','Title Proposal','Submit initial research proposal.'],
                ['user-check','Adviser Review','Review, consultation, and revision.'],
                ['presentation','Proposal Defense','Defense evaluation and required revisions.'],
                ['seal-check','Final Defense','Final evaluation and approval.'],
                ['archive','Archiving','Approved research stored in the institutional repository.']
            ] as $index=>$step)
            <article class="group relative flex flex-col items-center rounded-2xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-md transition-all duration-300 hover:-translate-y-1.5 hover:border-[#E5B72E]/40 hover:bg-white/10 hover:shadow-xl">
                <span class="relative z-10 flex h-12 w-12 items-center justify-center rounded-2xl border border-[#E5B72E]/40 bg-[#003D29] text-sm font-black text-[#E5B72E] shadow-sm">
                    {{ str_pad($index+1,2,'0',STR_PAD_LEFT) }}
                </span>
                <i class="ph-bold ph-{{ $step[0] }} mt-4 text-2xl text-[#E5B72E]"></i>
                <h3 class="mt-3 text-base font-extrabold text-white">{{ $step[1] }}</h3>
                <p class="mt-2 text-xs leading-relaxed text-white/75">{{ $step[2] }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

<!-- Features Grid Section -->
<section class="relative bg-[#F7FAF8] py-20 text-[#0F291E] sm:py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="text-center">
            <p class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-widest text-[#00633E]">
                <i class="ph-bold ph-folder-open text-base" aria-hidden="true"></i>One secure workspace
            </p>
            <h2 class="mt-2 text-3xl font-black tracking-tight text-[#003D29] sm:text-4xl">
                Everything your research needs
            </h2>
            <p class="mx-auto mt-2 max-w-xl text-base text-[#526359]">
                Documents, approvals, revisions, and progress kept in one place.
            </p>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['chart-line-up','Research Progress Tracking','Track the research from initial proposal through final archiving.'],
                ['file-arrow-up','Document Submission','Securely submit research documents and required revisions.'],
                ['checks','Reviews & Approvals','Keep adviser, facilitator, coordinator, and panel decisions organized.'],
                ['books','Research Archive','Maintain approved research records in the institutional repository.']
            ] as $feature)
            <article class="group relative flex flex-col justify-between rounded-2xl border border-[#CFE3D8] bg-white p-7 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-[#00633E]/40 hover:shadow-xl hover:shadow-[#003D29]/5">
                <div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#EAF5EF] text-[#00633E] transition-colors duration-300 group-hover:bg-[#003D29] group-hover:text-[#E5B72E]">
                        <i class="ph-bold ph-{{ $feature[0] }} text-2xl"></i>
                    </span>
                    <h3 class="mt-5 text-lg font-black text-[#003D29]">{{ $feature[1] }}</h3>
                    <p class="mt-2.5 text-xs sm:text-sm leading-relaxed text-[#526359]">{{ $feature[2] }}</p>
                </div>
                <div class="mt-6 flex items-center gap-1.5 text-xs font-bold text-[#00633E] opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                    Learn more <i class="ph-bold ph-caret-right text-xs"></i>
                </div>
            </article>
            @endforeach
        </div>

        <!-- Capability Pills Bar -->
        <div class="mt-12 flex flex-wrap items-center justify-center gap-3 rounded-2xl border border-[#CFE3D8] bg-white px-6 py-4 text-xs font-bold text-[#003D29] shadow-sm sm:gap-6">
            @foreach([
                ['shield-check','Secure Documents'],
                ['users-three','Role-Based Access'],
                ['flow-arrow','Review Workflow'],
                ['clock-counter-clockwise','Revision History'],
                ['archive','Research Archiving']
            ] as $capability)
                <span class="inline-flex items-center gap-2">
                    <i class="ph-bold ph-{{ $capability[0] }} text-base text-[#E5B72E]"></i>
                    {{ $capability[1] }}
                </span>
                @if(!$loop->last)
                    <span class="hidden h-4 w-px bg-[#CFE3D8] sm:block"></span>
                @endif
            @endforeach
        </div>
    </div>
</section>
</main>

<!-- Footer -->
<footer class="border-t border-white/10 bg-[#002318] px-6 py-10 text-center text-white/80">
    <div class="mx-auto max-w-7xl">
        <i class="ph-bold ph-buildings text-3xl text-[#E5B72E]" aria-hidden="true"></i>
        <p class="mt-3 text-base font-black text-white">NDMU Research Management System</p>
        <p class="mt-1 text-xs text-white/70">Notre Dame of Marbel University</p>
        <p class="mt-4 text-[10px] font-extrabold uppercase tracking-widest text-[#E5B72E]">University Information System</p>
        <p class="mt-4 text-xs text-white/60">&copy; 2026 Notre Dame of Marbel University. All rights reserved.</p>
    </div>
</footer>

<script>
    const siteHeader = document.querySelector('[data-site-header]');
    const updateHeader = () => siteHeader?.classList.toggle('is-scrolled', window.scrollY > 10);
    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();
</script>
</body>
</html>
