<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NDMU Research Management System</title>

    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        
        .hero-bg {
            background-image: linear-gradient(180deg, rgba(14, 58, 38, 0.88) 0%, rgba(10, 44, 28, 0.92) 100%), url('/images/ndmu-optimized.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        [data-scroll-section] {
            scroll-margin-top: 5rem;
        }

        .welcome-header {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
        }

        .welcome-header.is-scrolled {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 14px 40px rgba(15, 61, 36, 0.1);
        }

        .welcome-nav-link {
            position: relative;
            padding-block: 0.65rem;
            color: #6b7280;
            transition: color 220ms ease, transform 220ms ease;
        }

        .welcome-nav-link::after {
            position: absolute;
            right: 50%;
            bottom: 0.1rem;
            left: 50%;
            height: 2px;
            border-radius: 9999px;
            background: linear-gradient(90deg, #0e5c3a, #d69f24);
            content: '';
            opacity: 0;
            transition: right 260ms ease, left 260ms ease, opacity 180ms ease;
        }

        .welcome-nav-link:hover,
        .welcome-nav-link.is-active {
            color: #0e5c3a;
        }

        .welcome-nav-link.is-active::after {
            right: 0;
            left: 0;
            opacity: 1;
        }
    </style>
</head>
<body data-welcome-page class="font-sans antialiased text-gray-800 bg-white">

    <!-- Navigation -->
    <nav data-site-header class="welcome-header fixed w-full z-50 transition-all duration-300 border-b border-gray-100 shadow-sm">
        <div data-scroll-progress class="absolute inset-x-0 bottom-0 h-0.5 origin-left bg-gradient-to-r from-[#0e5c3a] via-[#1a7042] to-[#d69f24]" style="transform: scaleX(0)"></div>
        <div class="w-full px-6 md:px-12">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-11 w-auto">
                    <div class="flex flex-col leading-none">
                        <span class="font-heading font-extrabold text-lg text-[#0e5c3a] tracking-tight">NDMU</span>
                        <span class="text-[9px] font-bold text-[#d69f24] tracking-wider uppercase mt-1">Research Management</span>
                    </div>
                </div>

                <!-- Desktop Menu -->
                <div data-scrollspy-nav class="hidden md:flex items-center space-x-8">
                    <a href="#home" data-section-link="home" class="welcome-nav-link is-active text-sm font-semibold">Home</a>
                    <a href="#process" data-section-link="process" class="welcome-nav-link text-sm font-semibold">Process</a>
                </div>

                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center space-x-4">
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="px-6 py-2.5 text-sm font-bold text-[#0e5c3a] border border-[#0e5c3a] rounded-lg hover:bg-[#0e5c3a] hover:text-white transition-colors duration-300">Login</a>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="px-6 py-2.5 text-sm font-bold text-white bg-[#0e5c3a] rounded-lg hover:bg-[#0a4a2e] transition-colors duration-300 shadow-md shadow-[#0e5c3a]/15">Register</a>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" data-scroll-section class="hero-bg min-h-screen flex flex-col justify-center relative pt-20 overflow-hidden">
        <!-- Ambient Glowing Background Spheres -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[32rem] h-[32rem] bg-[#f8b803]/15 rounded-full blur-3xl pointer-events-none animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-[#0e5c3a]/30 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full z-10 text-center flex flex-col items-center relative">
            
            <!-- Institution Tag Badge -->
            <div class="inline-flex items-center gap-2.5 px-5 py-2 rounded-full border border-[#f8b803]/60 mb-8 mt-10 bg-white/10 backdrop-blur-md shadow-lg shadow-black/10">
                <span class="text-xs md:text-sm font-extrabold text-[#f8b803] tracking-wide uppercase">Notre Dame of Marbel University</span>
            </div>

            <!-- Main Heading with Gold Gradient -->
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-heading font-black text-white mb-6 tracking-tight leading-[1.1] drop-shadow-lg">
                NDMU Research <br/>
                <span class="bg-gradient-to-r from-[#f8b803] via-[#ffe066] to-[#eebc3f] bg-clip-text text-transparent drop-shadow-xl">Management System</span>
            </h1>
            
            <!-- Subtitle -->
            <p class="text-lg md:text-2xl text-gray-100 mb-10 max-w-3xl mx-auto font-light leading-relaxed drop-shadow-sm">
                Empowering Scholarly Research & Institutional Archiving Through Modern Digital Innovation
            </p>

            <!-- Call-to-action Buttons -->
            <div class="flex flex-col sm:flex-row items-center gap-5 mb-16">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="group px-9 py-4 text-base font-black text-[#0f3d24] bg-gradient-to-r from-[#eebc3f] via-[#f8b803] to-[#d69f24] rounded-2xl hover:scale-105 transition-all duration-300 flex items-center gap-3 shadow-xl shadow-[#f8b803]/25 hover:shadow-2xl hover:shadow-[#f8b803]/40">
                        <span>Get Started Now</span> 
                        <i class="ph ph-arrow-right font-bold text-lg group-hover:translate-x-1.5 transition-transform"></i>
                    </a>
                @elseif (Route::has('login'))
                    <a href="{{ route('login') }}" class="group px-9 py-4 text-base font-black text-[#0f3d24] bg-gradient-to-r from-[#eebc3f] via-[#f8b803] to-[#d69f24] rounded-2xl hover:scale-105 transition-all duration-300 flex items-center gap-3 shadow-xl shadow-[#f8b803]/25 hover:shadow-2xl hover:shadow-[#f8b803]/40">
                        <span>Access Portal</span>
                        <i class="ph ph-arrow-right font-bold text-lg group-hover:translate-x-1.5 transition-transform"></i>
                    </a>
                @endif
                <a href="#process" class="px-9 py-4 text-base font-bold text-white border border-white/30 bg-white/10 backdrop-blur-md rounded-2xl hover:bg-white/20 hover:border-white/50 hover:scale-105 transition-all duration-300 shadow-lg">
                    Explore Research Papers
                </a>
            </div>

            <!-- Stats Row (Glassmorphism Cards matching Image 1) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 w-full max-w-4xl mx-auto pb-6">
                <div class="glass-card rounded-3xl p-7 text-center transform hover:-translate-y-2 transition-all duration-300 border border-white/20 hover:border-[#f8b803]/60 group relative overflow-hidden shadow-xl">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-[#f8b803]/20 border border-[#f8b803]/40 flex items-center justify-center mb-5 text-[#f8b803] group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg shadow-[#f8b803]/20">
                        <i class="ph ph-users text-3xl font-bold"></i>
                    </div>
                    <h3 class="text-4xl font-heading font-black text-white mb-1 tracking-tight">500+</h3>
                    <p class="text-sm text-gray-200 font-extrabold tracking-wide">Active Researchers</p>
                </div>
                
                <div class="glass-card rounded-3xl p-7 text-center transform hover:-translate-y-2 transition-all duration-300 border border-white/20 hover:border-[#f8b803]/60 group relative overflow-hidden shadow-xl flex flex-col items-center justify-between">
                    <div>
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-[#f8b803]/20 border border-[#f8b803]/40 flex items-center justify-center mb-5 text-[#f8b803] group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg shadow-[#f8b803]/20">
                            <i class="ph ph-book-open-text text-3xl font-bold"></i>
                        </div>
                        <h3 class="text-4xl font-heading font-black text-white mb-1 tracking-tight">1,200+</h3>
                        <p class="text-sm text-gray-200 font-extrabold tracking-wide">Published Papers</p>
                    </div>
                    <!-- Down arrow caret button below middle card -->
                    <div class="mt-4">
                        <a href="#process" class="w-9 h-9 rounded-full bg-white/15 hover:bg-[#f8b803] text-white hover:text-[#0e5c3a] border border-white/30 hover:border-[#f8b803] flex items-center justify-center transition-all duration-300 shadow-md backdrop-blur-md">
                            <i class="ph ph-caret-down text-lg font-bold"></i>
                        </a>
                    </div>
                </div>
                
                <div class="glass-card rounded-3xl p-7 text-center transform hover:-translate-y-2 transition-all duration-300 border border-white/20 hover:border-[#f8b803]/60 group relative overflow-hidden shadow-xl">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-[#f8b803]/20 border border-[#f8b803]/40 flex items-center justify-center mb-5 text-[#f8b803] group-hover:scale-110 group-hover:rotate-3 transition-all duration-300 shadow-lg shadow-[#f8b803]/20">
                        <i class="ph ph-medal text-3xl font-bold"></i>
                    </div>
                    <h3 class="text-4xl font-heading font-black text-white mb-1 tracking-tight">150+</h3>
                    <p class="text-sm text-gray-200 font-extrabold tracking-wide">International Awards</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Research Journey Section (Workflow Pipeline matching Image 2) -->
    <section id="process" data-scroll-section class="py-24 bg-[#f6f9f7] overflow-hidden min-h-[80vh] flex flex-col justify-center">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full">
            <div class="text-center mb-16 space-y-2">
                <span class="text-xs font-extrabold tracking-wider text-[#d69f24] uppercase px-4 py-1.5 rounded-full bg-[#d69f24]/10 border border-[#d69f24]/30 inline-block">Workflow Pipeline</span>
                <h2 class="text-4xl md:text-5xl font-heading font-black text-[#0e5c3a]">Research Lifecycle Flow</h2>
                <p class="text-gray-500 text-base md:text-lg max-w-2xl mx-auto font-light">From initial title proposal to institutional archiving</p>
            </div>

            <!-- Connected Flow Stepper -->
            <div class="relative max-w-6xl mx-auto my-12">
                <!-- Connecting Line for Desktop -->
                <div class="hidden lg:block absolute top-14 left-[10%] right-[10%] h-0.5 bg-[#0e5c3a]/30 -z-0"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-4 relative z-10">
                    <!-- Step 1: Title Proposal -->
                    <div class="flex flex-col items-center text-center group">
                        <div class="relative mb-6">
                            <div class="w-28 h-28 rounded-full bg-[#0e5c3a] border-4 border-white text-white flex items-center justify-center shadow-xl group-hover:scale-105 transition-all duration-300">
                                <i class="ph ph-file-text text-4xl text-[#eebc3f]"></i>
                            </div>
                            <span class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-[#eebc3f] text-[#0e5c3a] font-black text-xs flex items-center justify-center shadow-md border-2 border-white">01</span>
                        </div>
                        <h4 class="text-base font-black text-[#0e5c3a] font-heading mb-2">Title Proposal</h4>
                        <p class="text-xs text-gray-500 leading-relaxed max-w-[180px]">Submit initial topic proposal and register research team</p>
                    </div>

                    <!-- Step 2: Adviser Review -->
                    <div class="flex flex-col items-center text-center group">
                        <div class="relative mb-6">
                            <div class="w-28 h-28 rounded-full bg-[#0e5c3a] border-4 border-white text-white flex items-center justify-center shadow-xl group-hover:scale-105 transition-all duration-300">
                                <i class="ph ph-user-gear text-4xl text-[#eebc3f]"></i>
                            </div>
                            <span class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-[#eebc3f] text-[#0e5c3a] font-black text-xs flex items-center justify-center shadow-md border-2 border-white">02</span>
                        </div>
                        <h4 class="text-base font-black text-[#0e5c3a] font-heading mb-2">Adviser Review</h4>
                        <p class="text-xs text-gray-500 leading-relaxed max-w-[180px]">Consultation sessions and document revision approvals</p>
                    </div>

                    <!-- Step 3: Proposal Defense -->
                    <div class="flex flex-col items-center text-center group">
                        <div class="relative mb-6">
                            <div class="w-28 h-28 rounded-full bg-[#0e5c3a] border-4 border-white text-white flex items-center justify-center shadow-xl group-hover:scale-105 transition-all duration-300">
                                <i class="ph ph-gavel text-4xl text-[#eebc3f]"></i>
                            </div>
                            <span class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-[#eebc3f] text-[#0e5c3a] font-black text-xs flex items-center justify-center shadow-md border-2 border-white">03</span>
                        </div>
                        <h4 class="text-base font-black text-[#0e5c3a] font-heading mb-2">Proposal Defense</h4>
                        <p class="text-xs text-gray-500 leading-relaxed max-w-[180px]">Schedule oral defense and submit chapter revisions</p>
                    </div>

                    <!-- Step 4: Final Defense -->
                    <div class="flex flex-col items-center text-center group">
                        <div class="relative mb-6">
                            <div class="w-28 h-28 rounded-full bg-[#0e5c3a] border-4 border-white text-white flex items-center justify-center shadow-xl group-hover:scale-105 transition-all duration-300">
                                <i class="ph ph-seal-check text-4xl text-[#eebc3f]"></i>
                            </div>
                            <span class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-[#eebc3f] text-[#0e5c3a] font-black text-xs flex items-center justify-center shadow-md border-2 border-white">04</span>
                        </div>
                        <h4 class="text-base font-black text-[#0e5c3a] font-heading mb-2">Final Defense</h4>
                        <p class="text-xs text-gray-500 leading-relaxed max-w-[180px]">Panel evaluation, scoring rubrics, and final signoff</p>
                    </div>

                    <!-- Step 5: Archiving -->
                    <div class="flex flex-col items-center text-center group">
                        <div class="relative mb-6">
                            <div class="w-28 h-28 rounded-full bg-[#0e5c3a] border-4 border-white text-white flex items-center justify-center shadow-xl group-hover:scale-105 transition-all duration-300">
                                <i class="ph ph-book-bookmark text-4xl text-[#eebc3f]"></i>
                            </div>
                            <span class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-[#eebc3f] text-[#0e5c3a] font-black text-xs flex items-center justify-center shadow-md border-2 border-white">05</span>
                        </div>
                        <h4 class="text-base font-black text-[#0e5c3a] font-heading mb-2">Archiving</h4>
                        <p class="text-xs text-gray-500 leading-relaxed max-w-[180px]">Published in official NDMU institutional repository</p>
                    </div>
                </div>
            </div>

            <div class="text-center pt-8">
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2.5 px-8 py-3.5 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white font-extrabold rounded-2xl transition-all shadow-lg hover:shadow-xl hover:scale-105">
                    <span>Start Your Research Journey</span>
                    <i class="ph ph-arrow-right font-bold"></i>
                </a>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const header = document.querySelector('[data-site-header]');
            const progress = document.querySelector('[data-scroll-progress]');

            function onScroll() {
                const scrollY = window.scrollY;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                const progressRatio = docHeight > 0 ? Math.min(Math.max(scrollY / docHeight, 0), 1) : 0;

                if (progress) {
                    progress.style.transform = `scaleX(${progressRatio})`;
                }

                if (header) {
                    if (scrollY > 15) {
                        header.classList.add('is-scrolled');
                    } else {
                        header.classList.remove('is-scrolled');
                    }
                }
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        });
    </script>
</body>
</html>
