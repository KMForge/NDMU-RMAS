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
            background-color: #043d2e;
            background-image: 
                radial-gradient(circle at 80% 20%, rgba(234, 179, 8, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 10% 80%, rgba(16, 185, 129, 0.08) 0%, transparent 45%),
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 36px 36px, 36px 36px;
        }
        .site-header { transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
        .site-header.is-scrolled {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px -10px rgba(4, 61, 46, 0.12);
        }
        .paper-lines {
            background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 27px, rgba(4, 61, 46, 0.05) 28px);
        }
        .gold-gradient-text {
            background: linear-gradient(135deg, #fef08a 0%, #facc15 50%, #eab308 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="m-0 overflow-x-hidden bg-[#faf8f2] text-[#0f291e] antialiased selection:bg-[#facc15] selection:text-[#043d2e]">

<!-- Header -->
<header data-site-header class="site-header sticky top-0 z-50 border-b border-[#043d2e]/10 bg-white/90 backdrop-blur-md">
    <div class="mx-auto flex min-h-[76px] max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="group flex items-center gap-3 transition-transform duration-200 hover:scale-[1.01]" aria-label="NDMU Research Management home">
            <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="Notre Dame of Marbel University seal" width="48" height="48" class="h-11 w-11 shrink-0 object-contain sm:h-12 sm:w-12 transition-transform duration-300 group-hover:rotate-3">
            <span class="border-l border-[#043d2e]/15 pl-3 leading-tight">
                <strong class="block text-base font-black tracking-tight text-[#043d2e] sm:text-lg">NDMU</strong>
                <span class="block text-[9px] font-extrabold uppercase tracking-[0.18em] text-[#b48811] sm:text-[10px]">Research Management System</span>
            </span>
        </a>
    </div>
</header>

<main>
<!-- Hero Section -->
<section class="hero-pattern relative overflow-hidden text-white">
    <!-- Decorative background ambient elements -->
    <div class="pointer-events-none absolute -right-24 -top-24 h-[420px] w-[420px] rounded-full border-[60px] border-[#facc15]/10 blur-xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-32 h-[500px] w-[500px] rounded-full border-[70px] border-emerald-400/5 blur-2xl"></div>

    <div class="relative mx-auto grid min-h-[660px] max-w-7xl items-center gap-12 px-6 py-16 lg:grid-cols-12 lg:px-8 lg:py-24">
        
        <!-- Left Hero Column -->
        <div class="relative z-10 lg:col-span-7">
            <div class="mb-6 inline-flex items-center gap-2.5 rounded-full border border-[#facc15]/30 bg-[#02281e]/80 px-4 py-2 text-xs font-bold uppercase tracking-widest text-[#fef08a] backdrop-blur-md shadow-inner">
                <span class="relative flex h-2 w-2">
                  <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#facc15] opacity-75"></span>
                  <span class="relative inline-flex h-2 w-2 rounded-full bg-[#facc15]"></span>
                </span>
                Internal · NDMU Research Management
            </div>

            <h1 class="text-4xl font-black leading-[1.08] tracking-tight sm:text-5xl lg:text-[3.6rem]">
                Manage research from <span class="gold-gradient-text">proposal</span> to institutional <span class="gold-gradient-text">archiving.</span>
            </h1>

            <p class="mt-6 max-w-xl text-base leading-relaxed text-[#d1e3da] sm:text-lg">
                NDMU Research Management System centralizes research submissions, reviews, approvals, defenses, revisions, and institutional archiving in one secure platform.
            </p>

            <div class="mt-9 flex flex-col gap-3.5 sm:flex-row sm:items-center">
                @if(Route::has('login'))
                    <a href="{{ route('login') }}" class="group inline-flex min-h-[52px] items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#facc15] to-[#eab308] px-7 text-sm font-extrabold text-[#043d2e] shadow-lg shadow-[#facc15]/20 transition duration-200 hover:from-[#fde047] hover:to-[#facc15] hover:shadow-xl hover:shadow-[#facc15]/30 hover:-translate-y-0.5">
                        Log in to Continue 
                        <i class="ph ph-arrow-right text-base transition-transform duration-200 group-hover:translate-x-1"></i>
                    </a>
                @endif
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/10 px-7 text-sm font-bold text-white backdrop-blur-md transition duration-200 hover:border-white/50 hover:bg-white/20 hover:-translate-y-0.5">
                        Create an Account
                    </a>
                @endif
            </div>

            <!-- Stats strip -->
            <div class="mt-12 grid grid-cols-3 gap-4 border-t border-white/10 pt-8">
                <div>
                    <span class="block text-2xl font-black text-[#facc15] sm:text-3xl">25</span>
                    <span class="mt-0.5 block text-xs font-semibold text-[#b8d5c7]">Official Forms</span>
                </div>
                <div>
                    <span class="block text-2xl font-black text-[#facc15] sm:text-3xl">13</span>
                    <span class="mt-0.5 block text-xs font-semibold text-[#b8d5c7]">Journey Stages</span>
                </div>
                <div>
                    <span class="block text-2xl font-black text-[#facc15] sm:text-3xl">100%</span>
                    <span class="mt-0.5 block text-xs font-semibold text-[#b8d5c7]">Secure RLS</span>
                </div>
            </div>
        </div>

        <!-- Right Preview Card Column -->
        <div class="relative lg:col-span-5 lg:justify-self-end w-full max-w-[500px]" aria-label="Sample research record under review">
            <!-- Background floating cards for depth -->
            <div class="absolute inset-x-6 bottom-0 top-12 rotate-3 rounded-3xl border border-[#facc15]/40 bg-[#d9a016] shadow-2xl transition duration-500 hover:rotate-4"></div>
            <div class="absolute inset-x-3 bottom-3 top-6 -rotate-2 rounded-3xl border border-[#dce3db] bg-[#eef1eb] shadow-lg"></div>

            <!-- Main paper card -->
            <article class="paper-lines relative rounded-3xl border border-white/90 bg-[#fffefb] p-6 text-[#0f291e] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.35)] sm:p-7">
                <div class="flex items-center justify-between gap-3 border-b border-[#043d2e]/10 pb-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-[#a27608]">Research Record</p>
                        <p class="mt-0.5 font-mono text-xs font-bold text-[#043d2e] sm:text-sm">RES-2026-00417</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-100 px-3 py-1 text-[10px] font-black tracking-wider text-amber-900 shadow-sm">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        UNDER REVIEW
                    </span>
                </div>

                <div class="py-5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#6b7c73]">Research title</p>
                    <h2 class="mt-1.5 text-lg font-black leading-snug text-[#043d2e] sm:text-xl">
                        Development of a Research Management Information System
                    </h2>
                    <div class="mt-5 grid grid-cols-2 gap-4 rounded-xl bg-[#f4f7f4] p-3.5 border border-[#043d2e]/5">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-wider text-[#798880]">Submitted by</p>
                            <p class="mt-0.5 text-xs font-bold text-[#043d2e]">Research Group</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-wider text-[#798880]">College / Program</p>
                            <p class="mt-0.5 text-xs font-bold text-[#043d2e]">NDMU Academic Unit</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-[#043d2e]/10 pt-4">
                    <div class="relative flex justify-between before:absolute before:left-[8%] before:right-[8%] before:top-3 before:h-0.5 before:bg-[#d8e2dc]">
                        @foreach([['check','Title Proposal','done'],['user-check','Adviser Review','done'],['presentation-chart','Proposal Defense','current'],['seal-check','Final Defense','next'],['archive','Archiving','next']] as $stage)
                        <div class="relative z-10 flex flex-col items-center text-center">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full border text-[11px] transition-transform duration-200 hover:scale-110 {{ $stage[2]==='done'?'border-[#043d2e] bg-[#043d2e] text-white':($stage[2]==='current'?'border-[#facc15] bg-[#facc15] text-[#043d2e] ring-4 ring-[#facc15]/20 shadow-sm':'border-[#ccd5cf] bg-[#faf8f2] text-[#86948c]') }}">
                                <i class="ph ph-{{ $stage[0] }}"></i>
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
<section class="hero-pattern relative border-t border-white/10 py-20 text-white sm:py-24">
    <div class="relative mx-auto max-w-7xl px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-[#facc15]">
                <i class="ph ph-path text-base" aria-hidden="true"></i>Research lifecycle
            </p>
            <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                How research moves
            </h2>
            <p class="mt-2 text-base text-[#cbe0d4]">
                One organized process from proposal to institutional archiving.
            </p>
        </div>

        <div class="relative mt-12 grid gap-6 md:grid-cols-5 md:gap-4 md:before:absolute md:before:left-[10%] md:before:right-[10%] md:before:top-7 md:before:border-t-2 md:before:border-dashed md:before:border-[#facc15]/50">
            @foreach([
                ['file-text','Title Proposal','Submit initial research proposal.'],
                ['user-check','Adviser Review','Review, consultation, and revision.'],
                ['presentation','Proposal Defense','Defense evaluation and required revisions.'],
                ['seal-check','Final Defense','Final evaluation and approval.'],
                ['archive','Archiving','Approved research stored in the institutional repository.']
            ] as $index=>$step)
            <article class="group relative flex flex-col items-center rounded-2xl border border-white/10 bg-[#02281e]/60 p-5 text-center backdrop-blur-md transition-all duration-300 hover:-translate-y-1.5 hover:border-[#facc15]/50 hover:bg-[#033327] hover:shadow-xl hover:shadow-[#043d2e]">
                <span class="relative z-10 flex h-14 w-14 items-center justify-center rounded-2xl border border-[#facc15]/60 bg-[#043d2e] text-base font-black text-[#facc15] shadow-inner ring-4 ring-white/5 transition-transform duration-300 group-hover:scale-110 group-hover:border-[#facc15]">
                    {{ str_pad($index+1,2,'0',STR_PAD_LEFT) }}
                </span>
                <i class="ph ph-{{ $step[0] }} mt-4 text-2xl text-[#facc15] transition-transform duration-300 group-hover:scale-110"></i>
                <h3 class="mt-2 text-base font-black text-white">{{ $step[1] }}</h3>
                <p class="mt-2 text-xs leading-relaxed text-[#b4d2c3]">{{ $step[2] }}</p>
            </article>
            @endforeach
        </div>
    </div>
</section>

<!-- Features Grid Section -->
<section class="relative bg-[#faf8f2] py-20 text-[#0f291e] sm:py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="text-center">
            <p class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-[#a27608]">
                <i class="ph ph-folder-open text-base" aria-hidden="true"></i>One secure workspace
            </p>
            <h2 class="mt-2 text-3xl font-black tracking-tight text-[#043d2e] sm:text-4xl">
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
            <article class="group relative flex flex-col justify-between rounded-2xl border border-[#043d2e]/10 bg-white p-7 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:border-[#043d2e]/30 hover:shadow-xl hover:shadow-[#043d2e]/10">
                <div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[#043d2e]/5 text-[#043d2e] transition-colors duration-300 group-hover:bg-[#043d2e] group-hover:text-[#facc15]">
                        <i class="ph ph-{{ $feature[0] }} text-2xl"></i>
                    </span>
                    <h3 class="mt-5 text-lg font-black text-[#043d2e]">{{ $feature[1] }}</h3>
                    <p class="mt-2.5 text-sm leading-relaxed text-[#526359]">{{ $feature[2] }}</p>
                </div>
                <div class="mt-6 flex items-center gap-1.5 text-xs font-bold text-[#043d2e] opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                    Learn more <i class="ph ph-caret-right text-xs"></i>
                </div>
            </article>
            @endforeach
        </div>

        <!-- Capability Pills Bar -->
        <div class="mt-12 flex flex-wrap items-center justify-center gap-3 rounded-2xl border border-[#043d2e]/10 bg-white px-6 py-4 text-xs font-bold text-[#043d2e] shadow-sm sm:gap-6">
            @foreach([
                ['shield-check','Secure Documents'],
                ['users-three','Role-Based Access'],
                ['flow-arrow','Review Workflow'],
                ['clock-counter-clockwise','Revision History'],
                ['archive','Research Archiving']
            ] as $capability)
                <span class="inline-flex items-center gap-2">
                    <i class="ph ph-{{ $capability[0] }} text-base text-[#a27608]"></i>
                    {{ $capability[1] }}
                </span>
                @if(!$loop->last)
                    <span class="hidden h-4 w-px bg-[#043d2e]/15 sm:block"></span>
                @endif
            @endforeach
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="hero-pattern relative overflow-hidden border-t border-white/10 px-6 py-20 text-center text-white">
    <div class="relative mx-auto max-w-4xl">
        <p class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-[#facc15]">
            <i class="ph ph-lock-key text-base" aria-hidden="true"></i>Authorized university access
        </p>
        <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
            Ready to manage your research?
        </h2>
        <p class="mx-auto mt-3 max-w-xl text-base text-[#d1e3da]">
            Access the NDMU Research Management System using your authorized university account.
        </p>
        <div class="mt-8 flex flex-col justify-center gap-3.5 sm:flex-row">
            @if(Route::has('login'))
                <a href="{{ route('login') }}" class="group inline-flex min-h-[50px] items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#facc15] to-[#eab308] px-7 text-sm font-extrabold text-[#043d2e] shadow-lg shadow-[#facc15]/20 transition duration-200 hover:from-[#fde047] hover:to-[#facc15] hover:shadow-xl hover:shadow-[#facc15]/30">
                    Log in to Continue 
                    <i class="ph ph-arrow-right text-base transition-transform duration-200 group-hover:translate-x-1"></i>
                </a>
            @endif
            @if(Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex min-h-[50px] items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/10 px-7 text-sm font-bold text-white backdrop-blur-md transition duration-200 hover:border-white/50 hover:bg-white/20">
                    <i class="ph ph-user-plus text-base" aria-hidden="true"></i>
                    Create an Account
                </a>
            @endif
        </div>
    </div>
</section>
</main>

<footer class="border-t border-white/10 bg-[#02281e] px-6 py-10 text-center text-[#cbe0d4]">
    <div class="mx-auto max-w-7xl">
        <i class="ph ph-buildings text-3xl text-[#facc15]" aria-hidden="true"></i>
        <p class="mt-3 text-base font-black text-white">NDMU Research Management System</p>
        <p class="mt-1 text-xs text-[#a2b8ab]">Notre Dame of Marbel University</p>
        <p class="mt-4 text-[10px] font-extrabold uppercase tracking-widest text-[#facc15]">University Information System</p>
        <p class="mt-4 text-xs text-[#87a393]">&copy; 2026 Notre Dame of Marbel University. All rights reserved.</p>
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
