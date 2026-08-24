<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="NDMU Research Management System for submissions, reviews, defenses, revisions, and archiving.">
    <title>NDMU Research Management System</title>
    
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        
        .hero-grid {
            background-color: #064b35;
            background-image: 
                radial-gradient(circle at 50% 30%, rgba(240, 200, 61, 0.12) 0%, transparent 65%),
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: 100% 100%, 42px 42px, 42px 42px;
        }
        
        .paper-lines {
            background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 24px, rgba(7, 94, 61, 0.07) 25px);
        }
    </style>
</head>
<body class="m-0 overflow-x-hidden bg-[#064b35] text-white antialiased">

<!-- Header Navigation -->
<header class="sticky top-0 z-50 border-b border-white/10 bg-[#043d2e]/95 backdrop-blur-md shadow-md">
    <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between px-6 lg:px-10">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="flex items-center gap-3.5 group" aria-label="NDMU Research Management home">
            <div class="p-1.5 rounded-xl bg-white/10 border border-white/15 group-hover:border-[#f0c83d]/50 transition duration-200">
                <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Seal" width="48" height="48" class="h-10 w-10 shrink-0 object-contain">
            </div>
            <div class="flex flex-col leading-none border-l-2 border-[#f0c83d] pl-3">
                <span class="font-heading font-black text-lg text-white tracking-tight">NDMU</span>
                <span class="text-[9px] font-extrabold uppercase tracking-[.15em] text-[#f0c83d] mt-0.5">Research Management</span>
            </div>
        </a>

        <!-- Auth Actions -->
        <div class="flex items-center gap-3">
            @if(Route::has('login'))
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-5 py-2.5 text-xs font-bold text-white transition duration-200 hover:bg-white/20 hover:border-white/50 sm:text-sm">
                    <i class="ph ph-sign-in text-base text-[#f0c83d]"></i>
                    <span>Login</span>
                </a>
            @endif
            @if(Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#e8b923] via-[#f0c83d] to-[#d7a916] px-5 py-2.5 text-xs font-extrabold text-[#17372c] shadow-lg transition duration-200 hover:scale-105 hover:shadow-xl sm:text-sm">
                    <i class="ph ph-user-plus text-base"></i>
                    <span>Register</span>
                </a>
            @endif
        </div>
    </div>
</header>

<main>
<!-- Hero Section -->
<section class="hero-grid relative overflow-hidden text-white py-16 sm:py-24">
    <div class="pointer-events-none absolute -right-28 -top-28 h-96 w-96 rounded-full border-[70px] border-[#e8b923]/10"></div>
    <div class="mx-auto grid min-h-[600px] max-w-7xl items-center gap-14 px-6 lg:grid-cols-[1.05fr_.95fr] lg:px-8">
        
        <!-- Left Text & Actions -->
        <div class="relative z-10 max-w-2xl">
            <div class="mb-6 inline-flex items-center gap-2.5 rounded-full border border-[#e8b923]/45 bg-[#053f2d] px-4 py-2 text-xs font-extrabold uppercase tracking-[.18em] text-[#f3cf5d] shadow-lg backdrop-blur-sm">
                <span class="h-2 w-2 rounded-full bg-[#e8b923] animate-ping"></span>
                <i class="ph ph-shield-check text-sm text-[#e8b923]"></i>
                <span>Internal · NDMU Research Management</span>
            </div>
            
            <h1 class="text-4xl font-black leading-[1.08] tracking-[-.035em] sm:text-5xl lg:text-[3.6rem]">
                Manage research from <span class="text-[#f0c83d]">proposal</span> to institutional <span class="text-[#f0c83d]">archiving.</span>
            </h1>
            
            <p class="mt-6 max-w-xl text-base leading-7 text-[#d6e3dc] sm:text-lg">
                NDMU Research Management System centralizes research submissions, reviews, approvals, defenses, revisions, and institutional archiving in one secure platform.
            </p>
            
            <div class="mt-9 flex flex-col gap-3.5 sm:flex-row">
                @if(Route::has('login'))
                    <a href="{{ route('login') }}" class="group inline-flex min-h-12 items-center justify-center gap-2.5 rounded-xl bg-gradient-to-r from-[#e8b923] via-[#f0c83d] to-[#d7a916] px-7 text-sm font-extrabold text-[#17372c] shadow-xl transition duration-200 hover:scale-105">
                        <span>Log in to Continue</span> 
                        <i class="ph ph-arrow-right text-base font-bold transition-transform duration-200 group-hover:translate-x-1"></i>
                    </a>
                @endif
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/40 bg-white/10 px-7 text-sm font-bold text-white shadow-md backdrop-blur-sm transition duration-200 hover:bg-white/20 hover:border-white/60">
                        <i class="ph ph-user-plus text-base text-[#f0c83d]"></i>
                        <span>Create an Account</span>
                    </a>
                @endif
            </div>

            <!-- Feature Pills -->
            <div class="mt-10 flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-black/20 px-3.5 py-1.5 text-xs font-bold text-[#e3f0ea] backdrop-blur-md">
                    <i class="ph ph-lock-key text-[#f0c83d]"></i> Secure access
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-black/20 px-3.5 py-1.5 text-xs font-bold text-[#e3f0ea] backdrop-blur-md">
                    <i class="ph ph-identification-card text-[#f0c83d]"></i> Role-based permissions
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-black/20 px-3.5 py-1.5 text-xs font-bold text-[#e3f0ea] backdrop-blur-md">
                    <i class="ph ph-archive text-[#f0c83d]"></i> Centralized records
                </span>
            </div>
        </div>

        <!-- Right Interactive System Preview Dashboard -->
        <div class="relative mx-auto w-full max-w-[520px] pb-6 pt-4 lg:mx-0 lg:justify-self-end">
            <!-- Ambient Glow Behind Preview Card -->
            <div class="absolute -inset-2 rounded-3xl bg-gradient-to-r from-[#f0c83d]/30 via-emerald-500/20 to-[#f0c83d]/20 blur-xl opacity-75"></div>
            
            <article class="relative rounded-3xl border border-white/25 bg-white/10 p-6 sm:p-8 backdrop-blur-xl shadow-[0_30px_80px_rgba(0,0,0,0.45)] text-white group hover:border-[#f0c83d]/50 transition duration-300">
                
                <!-- Card Header -->
                <div class="flex items-center justify-between border-b border-white/15 pb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#f0c83d]/20 border border-[#f0c83d]/40 flex items-center justify-center text-[#f0c83d] shadow-sm">
                            <i class="ph ph-squares-four text-xl font-bold"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black tracking-wider uppercase text-white font-heading">Portal Live Activity</p>
                            <p class="text-[10px] text-emerald-200 font-semibold flex items-center gap-1.5 mt-0.5">
                                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Real-time Research Tracking
                            </p>
                        </div>
                    </div>
                    <span class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[10px] font-extrabold tracking-wider text-[#f0c83d] uppercase backdrop-blur-md">
                        System Active
                    </span>
                </div>

                <!-- Interactive Quick Search Bar Preview -->
                <div class="mt-5 relative">
                    <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-black/25 border border-white/15 text-xs text-gray-300">
                        <i class="ph ph-magnifying-glass text-base text-[#f0c83d]"></i>
                        <span class="font-medium text-gray-300/80">Search research papers, authors, or topics...</span>
                        <span class="ml-auto px-2 py-0.5 rounded bg-white/10 text-[9px] font-mono text-gray-400">⌘K</span>
                    </div>
                </div>

                <!-- Recent Submissions & Activity Ticker -->
                <div class="mt-5 space-y-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-[#f0c83d] flex items-center gap-1.5">
                        <i class="ph ph-[#f0c83d] ph-clock-counter-clockwise text-xs"></i> Recent Submissions
                    </p>

                    <!-- Activity Item 1 -->
                    <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-300 flex items-center justify-center shrink-0">
                                <i class="ph ph-file-text text-lg"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-white truncate">AI Pest Detection System</p>
                                <p class="text-[10px] text-gray-300 truncate">Computer Studies • 2 mins ago</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-[9px] font-extrabold border border-emerald-500/30 shrink-0">
                            PROPOSAL
                        </span>
                    </div>

                    <!-- Activity Item 2 -->
                    <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#f0c83d]/20 text-[#f0c83d] flex items-center justify-center shrink-0">
                                <i class="ph ph-user-check text-lg"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-white truncate">IoT Smart Classroom</p>
                                <p class="text-[10px] text-gray-300 truncate">Engineering • 15 mins ago</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-[#f0c83d]/20 text-[#f0c83d] text-[9px] font-extrabold border border-[#f0c83d]/30 shrink-0">
                            ADVISER REVIEW
                        </span>
                    </div>

                    <!-- Activity Item 3 -->
                    <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-500/20 text-blue-300 flex items-center justify-center shrink-0">
                                <i class="ph ph-presentation-chart text-lg"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-white truncate">Community Health Portal</p>
                                <p class="text-[10px] text-gray-300 truncate">Health Sciences • Today 2:00 PM</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-blue-500/20 text-blue-300 text-[9px] font-extrabold border border-blue-500/30 shrink-0">
                            DEFENSE
                        </span>
                    </div>
                </div>

                <!-- Card Footer Actions -->
                <div class="mt-6 pt-4 border-t border-white/15 flex items-center justify-between text-xs">
                    <span class="text-gray-300 text-[11px] font-medium flex items-center gap-1.5">
                        <i class="ph ph-shield-check text-[#f0c83d]"></i> NDMU Verified System
                    </span>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-[#f0c83d] font-bold hover:underline text-xs">
                        <span>Access Dashboard</span>
                        <i class="ph ph-arrow-right"></i>
                    </a>
                </div>

            </article>
        </div>
    </div>
</section>

<!-- Research Lifecycle Section -->
<section class="bg-[#043d2e] py-20 sm:py-24 border-t border-white/10 text-white">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-xs font-extrabold uppercase tracking-[.2em] text-[#f0c83d] flex items-center gap-2">
                <i class="ph ph-path text-base"></i> Research lifecycle
            </p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">How research moves</h2>
            <p class="mt-3 text-[#d8e6df]">One organized process from proposal to institutional archiving.</p>
        </div>
        
        <div class="relative mt-14 grid gap-8 md:grid-cols-5 md:gap-4 md:before:absolute md:before:left-[9%] md:before:right-[9%] md:before:top-5 md:before:border-t md:before:border-dashed md:before:border-[#f0c83d]/60">
            @foreach([['file-text','Title Proposal','Submit initial research proposal.'],['user-check','Adviser Review','Review, consultation, and revision.'],['presentation-chart','Proposal Defense','Defense evaluation and required revisions.'],['seal-check','Final Defense','Final evaluation and approval.'],['archive','Archiving','Approved research stored in the institutional repository.']] as $index=>$step)
            <article class="relative grid grid-cols-[2.5rem_1fr] gap-4 md:block md:text-center group">
                <span class="relative z-10 flex h-11 w-11 items-center justify-center rounded-full border-4 border-[#043d2e] bg-[#075e3d] text-xs font-black text-[#f0c83d] shadow-lg group-hover:scale-110 transition duration-200 md:mx-auto">
                    {{ str_pad($index+1,2,'0',STR_PAD_LEFT) }}
                </span>
                <div class="md:mt-5">
                    <div class="w-10 h-10 mx-auto rounded-xl bg-white/10 flex items-center justify-center mb-3 text-[#f0c83d] group-hover:bg-[#f0c83d] group-hover:text-[#043d2e] transition duration-200">
                        <i class="ph ph-{{ $step[0] }} text-xl"></i>
                    </div>
                    <h3 class="mt-1 text-base font-extrabold text-white">{{ $step[1] }}</h3>
                    <p class="mx-auto mt-2 max-w-[190px] text-xs leading-5 text-[#c8dbd1] font-light">{{ $step[2] }}</p>
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>

<!-- One Secure Workspace Section -->
<section class="border-y border-white/10 bg-[#064b35] py-20 sm:py-24 text-white">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="text-center">
            <p class="text-xs font-extrabold uppercase tracking-[.2em] text-[#f0c83d] flex items-center justify-center gap-2">
                <i class="ph ph-folder-open text-base"></i> One secure workspace
            </p>
            <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">Everything your research needs</h2>
            <p class="mx-auto mt-3 max-w-xl text-[#d8e6df]">Documents, approvals, revisions, and progress kept in one place.</p>
        </div>
        
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['chart-line-up','Research Progress Tracking','Track the research from initial proposal through final archiving.'],['file-arrow-up','Document Submission','Securely submit research documents and required revisions.'],['checks','Reviews & Approvals','Keep adviser, facilitator, coordinator, and panel decisions organized.'],['books','Research Archive','Maintain approved research records in the institutional repository.']] as $feature)
            <article class="rounded-3xl border border-white/15 border-t-4 border-t-[#f0c83d] bg-white/5 p-7 shadow-xl hover:-translate-y-1.5 transition duration-300 backdrop-blur-sm group">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-[#f0c83d] group-hover:bg-[#f0c83d] group-hover:text-[#064b35] transition duration-200 shadow-md">
                    <i class="ph ph-{{ $feature[0] }} text-2xl"></i>
                </span>
                <h3 class="mt-6 text-lg font-extrabold text-white tracking-tight">{{ $feature[1] }}</h3>
                <p class="mt-3 text-xs leading-6 text-[#c8dbd1] font-light">{{ $feature[2] }}</p>
            </article>
            @endforeach
        </div>

        <div class="mt-12 flex flex-wrap items-center justify-center gap-x-4 gap-y-3 rounded-2xl border border-white/15 bg-black/20 px-6 py-4 text-xs font-bold text-[#e0ebe5] shadow-inner">
            @foreach([['shield-check','Secure Documents'],['users-three','Role-Based Access'],['flow-arrow','Review Workflow'],['clock-counter-clockwise','Revision History'],['archive','Research Archiving']] as $capability)
                <span class="inline-flex items-center gap-2">
                    <i class="ph ph-{{ $capability[0] }} text-base text-[#f0c83d]"></i>{{ $capability[1] }}
                </span>
                @if(!$loop->last)
                    <span class="hidden h-4 w-px bg-[#f0c83d]/30 sm:block"></span>
                @endif
            @endforeach
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="relative overflow-hidden bg-gradient-to-br from-[#043d2e] via-[#064b35] to-[#075e3d] px-6 py-16 text-center text-white border-b border-white/10">
    <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-52 rotate-12 rounded-2xl border border-white/5 bg-white/[.025]"></div>
    <div class="relative">
        <p class="inline-flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-[.2em] text-[#f0c83d]">
            <i class="ph ph-lock-key text-base"></i> Authorized university access
        </p>
        <h2 class="mt-3 text-2xl font-black sm:text-3xl">Ready to manage your research?</h2>
        <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-[#d8e6df]">Access the NDMU Research Management System using your authorized university account.</p>
        
        <div class="mt-7 flex flex-col justify-center gap-3.5 sm:flex-row">
            @if(Route::has('login'))
                <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#e8b923] via-[#f0c83d] to-[#d7a916] px-7 text-sm font-extrabold text-[#17372c] transition hover:scale-105 shadow-xl">
                    <span>Log in to Continue</span> 
                    <i class="ph ph-arrow-right font-bold"></i>
                </a>
            @endif 
            @if(Route::has('register'))
                <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/40 bg-white/10 px-7 text-sm font-bold text-white transition hover:bg-white/20">
                    <i class="ph ph-user-plus text-base text-[#f0c83d]"></i>
                    <span>Create an Account</span>
                </a>
            @endif
        </div>
    </div>
</section>
</main>

<!-- Footer -->
<footer class="bg-[#032b20] border-t border-white/10 px-6 py-8 text-center text-xs text-[#c8dbd1]">
    <div class="flex items-center justify-center gap-2 text-[#f0c83d] mb-2">
        <i class="ph ph-buildings text-xl"></i>
    </div>
    <p class="text-sm font-extrabold text-white">NDMU Research Management System</p>
    <p class="mt-1 text-xs text-[#a27608] font-semibold">Notre Dame of Marbel University</p>
    <p class="mt-4 text-[11px] text-gray-400">&copy; 2026 Notre Dame of Marbel University. All rights reserved.</p>
</footer>

</body>
</html>
