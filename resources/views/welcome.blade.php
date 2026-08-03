<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NDMU Research Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        
        .hero-bg {
            background-image: linear-gradient(180deg, rgba(14, 58, 38, 0.85) 0%, rgba(10, 44, 28, 0.9) 100%), url('{{ asset('images/ndmu-optimized.jpg') }}');
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

        .welcome-nav-link.is-active {
            transform: translateY(-1px);
        }

        .welcome-nav-link.is-active::after {
            right: 0;
            left: 0;
            opacity: 1;
        }

        .scroll-reveal {
            opacity: 0;
            transform: translateY(2.25rem) scale(0.99);
            transition:
                opacity 700ms cubic-bezier(0.22, 1, 0.36, 1),
                transform 850ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .scroll-reveal.is-visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }

            .welcome-nav-link,
            .welcome-nav-link::after,
            .scroll-reveal {
                transition: none;
            }

            .scroll-reveal {
                opacity: 1;
                transform: none;
            }
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
                    <a href="#about" data-section-link="about" class="welcome-nav-link text-sm font-semibold">About</a>
                    <a href="#achievements" data-section-link="achievements" class="welcome-nav-link text-sm font-semibold">Achievements</a>
                    <a href="#process" data-section-link="process" class="welcome-nav-link text-sm font-semibold">Process</a>
                    <a href="#events" data-section-link="events" class="welcome-nav-link text-sm font-semibold">Events</a>
                    <a href="#contact" data-section-link="contact" class="welcome-nav-link text-sm font-semibold">Contact</a>
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
    <section id="home" data-scroll-section class="hero-bg min-h-screen flex flex-col justify-center relative pt-20">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full z-10 text-center flex flex-col items-center">
            
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-[#f8b803] mb-8 mt-12 bg-white/5 backdrop-blur-sm">
                <span class="w-2 h-2 rounded-full bg-[#f8b803]"></span>
                <span class="text-sm font-medium text-[#f8b803]">Notre Dame of Marbel University</span>
            </div>

            <h1 class="text-5xl md:text-7xl font-heading font-extrabold text-white mb-6 tracking-tight leading-[1.15]">
                NDMU Research <br/>
                <span class="text-[#f8b803]">Management System</span>
            </h1>
            
            <p class="text-lg md:text-xl text-gray-200 mb-10 max-w-2xl mx-auto font-light leading-relaxed">
                Empowering Research Through Digital Innovation
            </p>

            <div class="flex flex-col sm:flex-row items-center gap-4 mb-24">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="group px-8 py-3.5 text-base font-bold text-[#0f3d24] bg-[#eebc3f] rounded-md hover:bg-[#d69f24] transition-all duration-300 flex items-center gap-2 shadow-lg shadow-[#eebc3f]/20">
                        Get Started 
                        <i class="ph ph-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                @elseif (Route::has('login'))
                    <a href="{{ route('login') }}" class="group px-8 py-3.5 text-base font-bold text-[#0f3d24] bg-[#eebc3f] rounded-md hover:bg-[#d69f24] transition-all duration-300 flex items-center gap-2 shadow-lg shadow-[#eebc3f]/20">
                        Login to Continue
                        <i class="ph ph-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                @endif
                <a href="#achievements" class="px-8 py-3.5 text-base font-semibold text-white border border-white/20 bg-white/5 rounded-md hover:bg-white/10 hover:border-white/40 transition-all duration-300">
                    Explore Research
                </a>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 w-full max-w-4xl mx-auto pb-12">
                <div class="glass-card rounded-2xl p-6 text-center transform hover:-translate-y-1 transition-transform duration-300">
                    <div class="w-12 h-12 mx-auto rounded-full bg-white/10 flex items-center justify-center mb-4">
                        <i class="ph ph-users text-2xl text-[#f8b803]"></i>
                    </div>
                    <h3 class="text-3xl font-heading font-bold text-white mb-1">500+</h3>
                    <p class="text-sm text-gray-300 font-medium">Active Researchers</p>
                </div>
                
                <div class="glass-card rounded-2xl p-6 text-center transform hover:-translate-y-1 transition-transform duration-300">
                    <div class="w-12 h-12 mx-auto rounded-full bg-white/10 flex items-center justify-center mb-4">
                        <i class="ph ph-book-open-text text-2xl text-[#f8b803]"></i>
                    </div>
                    <h3 class="text-3xl font-heading font-bold text-white mb-1">1,200+</h3>
                    <p class="text-sm text-gray-300 font-medium">Published Papers</p>
                </div>
                
                <div class="glass-card rounded-2xl p-6 text-center transform hover:-translate-y-1 transition-transform duration-300">
                    <div class="w-12 h-12 mx-auto rounded-full bg-white/10 flex items-center justify-center mb-4">
                        <i class="ph ph-medal text-2xl text-[#f8b803]"></i>
                    </div>
                    <h3 class="text-3xl font-heading font-bold text-white mb-1">150+</h3>
                    <p class="text-sm text-gray-300 font-medium">International Awards</p>
                </div>
            </div>
        </div>

        <!-- Scroll down indicator -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 animate-bounce">
            <a href="#about" class="text-white/60 hover:text-white transition-colors">
                <i class="ph ph-caret-down text-2xl"></i>
            </a>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" data-scroll-section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-sm font-bold tracking-widest text-[#d69f24] uppercase mb-2 block">About Us</span>
                <h2 class="text-4xl md:text-5xl font-heading font-bold text-[#0e5c3a] mb-4">Notre Dame of Marbel University</h2>
                <p class="text-gray-500 text-lg max-w-3xl mx-auto font-light">A premier Catholic educational institution founded by the Marist Brothers in 1946</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <!-- Left Image with 360 Badge -->
                <div class="relative rounded-3xl overflow-hidden shadow-xl group">
                    <img src="{{ asset('images/ndmu-optimized.jpg') }}" alt="NDMU Campus" loading="lazy" decoding="async" class="w-full h-[500px] object-cover group-hover:scale-105 transition-transform duration-700">
                    <div class="absolute top-4 right-4 bg-[#eebc3f] text-[#0f3d24] text-xs font-extrabold px-4 py-2 rounded-full shadow-lg flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#0f3d24]"></span>
                        360° Tour
                    </div>
                </div>

                <!-- Right Content (Cards) -->
                <div class="space-y-6">
                    <!-- Vision Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex gap-5 hover:shadow-md transition-shadow">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-[#f2f8f4] flex items-center justify-center text-[#1a7042]">
                            <i class="ph ph-target text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-heading font-bold text-[#0e5c3a] mb-1">Our Vision</h4>
                            <p class="text-gray-500 text-sm leading-relaxed">To be a center of academic excellence, promoting integral human development and social transformation rooted in the Gospel values.</p>
                        </div>
                    </div>

                    <!-- Mission Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex gap-5 hover:shadow-md transition-shadow">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-[#fffdf5] flex items-center justify-center text-[#d69f24]">
                            <i class="ph ph-heart text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-heading font-bold text-[#0e5c3a] mb-1">Our Mission</h4>
                            <p class="text-gray-500 text-sm leading-relaxed">To provide quality Catholic education that develops competent, compassionate, and committed individuals ready to serve God and country.</p>
                        </div>
                    </div>

                    <!-- Research Excellence Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex gap-5 hover:shadow-md transition-shadow">
                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-[#f5f8ff] flex items-center justify-center text-[#2b5ba3]">
                            <i class="ph ph-graduation-cap text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-heading font-bold text-[#0e5c3a] mb-1">Research Excellence</h4>
                            <p class="text-gray-500 text-sm leading-relaxed">Leading innovation in research across multiple disciplines with international recognition and community impact.</p>
                        </div>
                    </div>

                    <div class="pt-4">
                        <a href="#" class="inline-flex items-center px-6 py-3 bg-[#0e5c3a] text-white font-bold rounded-xl hover:bg-[#0a4a2e] transition-colors shadow-md shadow-[#0e5c3a]/15">
                            Read More About NDMU
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Research Achievements Section -->
    <section id="achievements" data-scroll-section class="py-24 bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-sm font-bold tracking-widest text-[#d69f24] uppercase mb-2 block">Excellence</span>
                <h2 class="text-4xl md:text-5xl font-heading font-bold text-[#0e5c3a] mb-4">Research Achievements</h2>
                <p class="text-gray-500 text-lg">International and National Award-Winning Research</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <!-- Achievement 1 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 group hover:-translate-y-2 transition-transform duration-300 flex flex-col">
                    <div class="bg-[#0e5c3a] p-8 h-44 relative overflow-hidden flex flex-col justify-end">
                        <div class="absolute -right-6 -top-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:bg-white/20 transition-colors"></div>
                        <div class="w-12 h-12 border border-white/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ph ph-cpu text-2xl text-white"></i>
                        </div>
                        <div class="flex items-center gap-1.5 text-[#f8b803] text-sm font-semibold">
                            <span>🏆</span> Best Research Paper — Gold Medal
                        </div>
                    </div>
                    <div class="p-8 flex flex-col flex-grow">
                        <h3 class="text-xl font-heading font-bold text-[#0e5c3a] mb-4 leading-snug">AI-Powered Agricultural Pest Detection System</h3>
                        <div class="space-y-3 mb-6 text-sm">
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-users text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Researchers:</strong> <span class="text-gray-500">Maria Santos, Juan Dela Cruz</span></span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-graduation-cap text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Adviser:</strong> <span class="text-gray-500">Dr. Roberto Garcia</span></span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-globe text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Event:</strong> <span class="text-gray-500">International Research Symposium 2025</span></span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-map-pin text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Location:</strong> <span class="text-gray-500">Singapore</span></span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-50">
                            <span class="px-3 py-1 bg-[#f2f8f4] text-[#0e5c3a] text-xs font-semibold rounded-full">Artificial Intelligence</span>
                            <span class="text-xs text-gray-400 font-medium">March 2025</span>
                        </div>
                    </div>
                </div>

                <!-- Achievement 2 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 group hover:-translate-y-2 transition-transform duration-300 flex flex-col">
                    <div class="bg-[#0e5c3a] p-8 h-44 relative overflow-hidden flex flex-col justify-end">
                        <div class="absolute -right-6 -top-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:bg-white/20 transition-colors"></div>
                        <div class="w-12 h-12 border border-white/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ph ph-microscope text-2xl text-white"></i>
                        </div>
                        <div class="flex items-center gap-1.5 text-[#f8b803] text-sm font-semibold">
                            <span>🏆</span> Innovation Excellence Award
                        </div>
                    </div>
                    <div class="p-8 flex flex-col flex-grow">
                        <h3 class="text-xl font-heading font-bold text-[#0e5c3a] mb-4 leading-snug">IoT-Based Smart Classroom Management</h3>
                        <div class="space-y-3 mb-6 text-sm">
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-users text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Researchers:</strong> <span class="text-gray-500">Anna Reyes, Carlos Mendoza</span></span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-graduation-cap text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Adviser:</strong> <span class="text-gray-500">Dr. Patricia Cruz</span></span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-globe text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Event:</strong> <span class="text-gray-500">ASEAN Innovation Awards</span></span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-map-pin text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Location:</strong> <span class="text-gray-500">Thailand</span></span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-50">
                            <span class="px-3 py-1 bg-[#f2f8f4] text-[#0e5c3a] text-xs font-semibold rounded-full">Internet of Things</span>
                            <span class="text-xs text-gray-400 font-medium">January 2025</span>
                        </div>
                    </div>
                </div>

                <!-- Achievement 3 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 group hover:-translate-y-2 transition-transform duration-300 flex flex-col">
                    <div class="bg-[#0e5c3a] p-8 h-44 relative overflow-hidden flex flex-col justify-end">
                        <div class="absolute -right-6 -top-6 w-28 h-28 bg-white/10 rounded-full blur-xl group-hover:bg-white/20 transition-colors"></div>
                        <div class="w-12 h-12 border border-white/20 rounded-xl flex items-center justify-center mb-4">
                            <i class="ph ph-heartbeat text-2xl text-white"></i>
                        </div>
                        <div class="flex items-center gap-1.5 text-[#f8b803] text-sm font-semibold">
                            <span>🏆</span> Outstanding Research — Silver Medal
                        </div>
                    </div>
                    <div class="p-8 flex flex-col flex-grow">
                        <h3 class="text-xl font-heading font-bold text-[#0e5c3a] mb-4 leading-snug">Community Health Information System</h3>
                        <div class="space-y-3 mb-6 text-sm">
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-users text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Researchers:</strong> <span class="text-gray-500">Luis Fernandez, Sarah Gonzales</span></span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-graduation-cap text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Adviser:</strong> <span class="text-gray-500">Dr. Michael Tan</span></span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-globe text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Event:</strong> <span class="text-gray-500">National Research Congress</span></span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <i class="ph ph-map-pin text-[#0e5c3a] text-lg"></i>
                                <span><strong class="text-gray-700">Location:</strong> <span class="text-gray-500">Manila, Philippines</span></span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-50">
                            <span class="px-3 py-1 bg-[#f2f8f4] text-[#0e5c3a] text-xs font-semibold rounded-full">Healthcare Technology</span>
                            <span class="text-xs text-gray-400 font-medium">November 2024</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="#" class="inline-flex items-center gap-2 px-6 py-3 bg-[#0e5c3a] text-white font-bold rounded-xl hover:bg-[#0a4a2e] transition-colors shadow-md shadow-[#0e5c3a]/15">
                    View All Achievements <i class="ph ph-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Research Journey Section -->
    <section id="process" data-scroll-section class="py-24 bg-[#f2f6f4]">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-sm font-bold tracking-widest text-[#d69f24] uppercase mb-2 block">How It Works</span>
                <h2 class="text-4xl md:text-5xl font-heading font-bold text-[#0e5c3a] mb-4">Research Journey</h2>
                <p class="text-gray-500 text-lg">Step-by-step process from proposal to publication</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16 relative">
                <!-- Connecting Line for Desktop -->
                <div class="hidden lg:block absolute top-14 left-[12%] right-[12%] h-[1.5px] bg-[#dce8e0] -z-0"></div>

                <!-- Step 1 -->
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 relative group hover:shadow-md transition-shadow z-10">
                    <div class="w-12 h-12 bg-[#0e5c3a] text-white rounded-xl flex items-center justify-center font-heading font-bold text-xl mb-6 shadow-lg shadow-[#0e5c3a]/25 group-hover:scale-105 transition-transform">1</div>
                    <i class="ph ph-file-text text-3xl text-[#0e5c3a] mb-4 block"></i>
                    <h4 class="text-lg font-heading font-bold text-[#0e5c3a] mb-3">Proposal Submission</h4>
                    <p class="text-gray-500 text-sm leading-relaxed">Submit your research proposal with complete documentation for initial review.</p>
                </div>

                <!-- Step 2 -->
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 relative group hover:shadow-md transition-shadow z-10">
                    <div class="w-12 h-12 bg-[#0e5c3a] text-white rounded-xl flex items-center justify-center font-heading font-bold text-xl mb-6 shadow-lg shadow-[#0e5c3a]/25 group-hover:scale-105 transition-transform">2</div>
                    <i class="ph ph-users text-3xl text-[#0e5c3a] mb-4 block"></i>
                    <h4 class="text-lg font-heading font-bold text-[#0e5c3a] mb-3">Adviser Review</h4>
                    <p class="text-gray-500 text-sm leading-relaxed">Get expert feedback and guidance from your assigned research adviser.</p>
                </div>

                <!-- Step 3 -->
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 relative group hover:shadow-md transition-shadow z-10">
                    <div class="w-12 h-12 bg-[#0e5c3a] text-white rounded-xl flex items-center justify-center font-heading font-bold text-xl mb-6 shadow-lg shadow-[#0e5c3a]/25 group-hover:scale-105 transition-transform">3</div>
                    <i class="ph ph-calendar text-3xl text-[#0e5c3a] mb-4 block"></i>
                    <h4 class="text-lg font-heading font-bold text-[#0e5c3a] mb-3">Defense Scheduling</h4>
                    <p class="text-gray-500 text-sm leading-relaxed">Schedule and conduct proposal or final oral defense with the panel.</p>
                </div>

                <!-- Step 4 -->
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 relative group hover:shadow-md transition-shadow z-10">
                    <div class="w-12 h-12 bg-[#0e5c3a] text-white rounded-xl flex items-center justify-center font-heading font-bold text-xl mb-6 shadow-lg shadow-[#0e5c3a]/25 group-hover:scale-105 transition-transform">4</div>
                    <i class="ph ph-trophy text-3xl text-[#0e5c3a] mb-4 block"></i>
                    <h4 class="text-lg font-heading font-bold text-[#0e5c3a] mb-3">Publication</h4>
                    <p class="text-gray-500 text-sm leading-relaxed">Publish your research and earn national or international recognition.</p>
                </div>
            </div>

            <div class="text-center">
                <a href="#" class="inline-block px-6 py-3 bg-[#0e5c3a] text-white font-bold rounded-xl hover:bg-[#0a4a2e] transition-colors shadow-md shadow-[#0e5c3a]/15">
                    Learn More About The Process
                </a>
            </div>
        </div>
    </section>

    <!-- Research Forum & Events -->
    <section id="events" data-scroll-section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-sm font-bold tracking-widest text-[#d69f24] uppercase mb-2 block">Upcoming</span>
                <h2 class="text-4xl md:text-5xl font-heading font-bold text-[#0e5c3a] mb-4">Research Forum & Events</h2>
                <p class="text-gray-500 text-lg">Join our upcoming research events and symposiums</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Event 1 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 flex flex-col group hover:-translate-y-2 transition-transform duration-300">
                    <div class="bg-[#1f734c] p-8 h-40 flex flex-col justify-between relative">
                        <div class="flex justify-between items-start">
                            <span class="px-3 py-1 bg-white/20 text-white text-xs font-semibold rounded-full backdrop-blur-sm">Conference</span>
                            <i class="ph ph-calendar-blank text-white/60 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-heading font-bold text-white pr-4">International Research Symposium 2026</h3>
                    </div>
                    <div class="p-8 flex flex-col flex-grow">
                        <div class="space-y-4 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="ph ph-clock text-[#1f734c] text-lg"></i>
                                <span>June 15–17, 2026</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="ph ph-map-pin text-[#1f734c] text-lg"></i>
                                <span>NDMU Main Campus</span>
                            </div>
                        </div>
                        <div class="bg-[#fff9e6] text-[#b38517] text-sm font-bold py-3 px-4 rounded-xl text-center mb-6 mt-auto">
                            45 days remaining
                        </div>
                        <a href="#" class="block w-full py-3.5 text-center bg-[#1f734c] text-white font-bold rounded-xl hover:bg-[#165638] transition-colors shadow-md shadow-[#1f734c]/15">Register Now</a>
                    </div>
                </div>

                <!-- Event 2 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 flex flex-col group hover:-translate-y-2 transition-transform duration-300">
                    <div class="bg-[#8b46d2] p-8 h-40 flex flex-col justify-between relative">
                        <div class="flex justify-between items-start">
                            <span class="px-3 py-1 bg-white/20 text-white text-xs font-semibold rounded-full backdrop-blur-sm">Defense</span>
                            <i class="ph ph-calendar-blank text-white/60 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-heading font-bold text-white pr-4">Final Defense Sessions — June Batch</h3>
                    </div>
                    <div class="p-8 flex flex-col flex-grow">
                        <div class="space-y-4 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="ph ph-clock text-[#8b46d2] text-lg"></i>
                                <span>June 20, 2026</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="ph ph-map-pin text-[#8b46d2] text-lg"></i>
                                <span>Research Building, Hall A</span>
                            </div>
                        </div>
                        <div class="bg-[#fff9e6] text-[#b38517] text-sm font-bold py-3 px-4 rounded-xl text-center mb-6 mt-auto">
                            50 days remaining
                        </div>
                        <a href="#" class="block w-full py-3.5 text-center bg-[#8b46d2] text-white font-bold rounded-xl hover:bg-[#7236b2] transition-colors shadow-md shadow-[#8b46d2]/15">Register Now</a>
                    </div>
                </div>

                <!-- Event 3 -->
                <div class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 flex flex-col group hover:-translate-y-2 transition-transform duration-300">
                    <div class="bg-[#c45a27] p-8 h-40 flex flex-col justify-between relative">
                        <div class="flex justify-between items-start">
                            <span class="px-3 py-1 bg-white/20 text-white text-xs font-semibold rounded-full backdrop-blur-sm">Workshop</span>
                            <i class="ph ph-calendar-blank text-white/60 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-heading font-bold text-white pr-4">Research Methodology Workshop</h3>
                    </div>
                    <div class="p-8 flex flex-col flex-grow">
                        <div class="space-y-4 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="ph ph-clock text-[#c45a27] text-lg"></i>
                                <span>May 25, 2026</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600">
                                <i class="ph ph-map-pin text-[#c45a27] text-lg"></i>
                                <span>Online via Zoom</span>
                            </div>
                        </div>
                        <div class="bg-[#fff9e6] text-[#b38517] text-sm font-bold py-3 px-4 rounded-xl text-center mb-6 mt-auto">
                            6 days remaining
                        </div>
                        <a href="#" class="block w-full py-3.5 text-center bg-[#c45a27] text-white font-bold rounded-xl hover:bg-[#a6481b] transition-colors shadow-md shadow-[#c45a27]/15">Register Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Research Projects Section -->
    <section class="py-24 bg-[#f2f6f4] border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-sm font-bold tracking-widest text-[#d69f24] uppercase mb-2 block">Explore</span>
                <h2 class="text-4xl md:text-5xl font-heading font-bold text-[#0e5c3a] mb-4">Featured Research Projects</h2>
                <p class="text-gray-500 text-lg">Discover cutting-edge research across various disciplines</p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Project 1 -->
                <div class="bg-white rounded-[2rem] overflow-hidden shadow-md border border-gray-100 flex flex-col group hover:-translate-y-2 transition-transform duration-300">
                    <div class="bg-[#1e5adb] p-8 text-center flex flex-col items-center justify-center relative overflow-hidden">
                        <div class="w-14 h-14 bg-white/15 border border-white/20 rounded-2xl flex items-center justify-center mb-5">
                            <i class="ph ph-cpu text-3xl text-white"></i>
                        </div>
                        <h4 class="text-white font-bold text-lg font-heading mb-3">Artificial Intelligence</h4>
                        <span class="text-white font-extrabold text-5xl font-heading mb-2 leading-none">85</span>
                        <span class="text-white/80 text-xs font-medium">Research Papers</span>
                    </div>
                    <div class="py-5 text-center bg-white">
                        <a href="#" class="text-gray-500 hover:text-gray-800 text-sm font-semibold transition-colors">
                            View All Projects →
                        </a>
                    </div>
                </div>

                <!-- Project 2 -->
                <div class="bg-white rounded-[2rem] overflow-hidden shadow-md border border-gray-100 flex flex-col group hover:-translate-y-2 transition-transform duration-300">
                    <div class="bg-[#d32f2f] p-8 text-center flex flex-col items-center justify-center relative overflow-hidden">
                        <div class="w-14 h-14 bg-white/15 border border-white/20 rounded-2xl flex items-center justify-center mb-5">
                            <i class="ph ph-heart text-3xl text-white"></i>
                        </div>
                        <h4 class="text-white font-bold text-lg font-heading mb-3">Healthcare</h4>
                        <span class="text-white font-extrabold text-5xl font-heading mb-2 leading-none">62</span>
                        <span class="text-white/80 text-xs font-medium">Research Papers</span>
                    </div>
                    <div class="py-5 text-center bg-white">
                        <a href="#" class="text-gray-500 hover:text-gray-800 text-sm font-semibold transition-colors">
                            View All Projects →
                        </a>
                    </div>
                </div>

                <!-- Project 3 -->
                <div class="bg-white rounded-[2rem] overflow-hidden shadow-md border border-gray-100 flex flex-col group hover:-translate-y-2 transition-transform duration-300">
                    <div class="bg-[#7b2cbf] p-8 text-center flex flex-col items-center justify-center relative overflow-hidden">
                        <div class="w-14 h-14 bg-white/15 border border-white/20 rounded-2xl flex items-center justify-center mb-5">
                            <i class="ph ph-graduation-cap text-3xl text-white"></i>
                        </div>
                        <h4 class="text-white font-bold text-lg font-heading mb-3">Education</h4>
                        <span class="text-white font-extrabold text-5xl font-heading mb-2 leading-none">94</span>
                        <span class="text-white/80 text-xs font-medium">Research Papers</span>
                    </div>
                    <div class="py-5 text-center bg-white">
                        <a href="#" class="text-gray-500 hover:text-gray-800 text-sm font-semibold transition-colors">
                            View All Projects →
                        </a>
                    </div>
                </div>

                <!-- Project 4 -->
                <div class="bg-white rounded-[2rem] overflow-hidden shadow-md border border-gray-100 flex flex-col group hover:-translate-y-2 transition-transform duration-300">
                    <div class="bg-[#0e5c3a] p-8 text-center flex flex-col items-center justify-center relative overflow-hidden">
                        <div class="w-14 h-14 bg-white/15 border border-white/20 rounded-2xl flex items-center justify-center mb-5">
                            <i class="ph ph-plant text-3xl text-white"></i>
                        </div>
                        <h4 class="text-white font-bold text-lg font-heading mb-3">Agriculture</h4>
                        <span class="text-white font-extrabold text-5xl font-heading mb-2 leading-none">48</span>
                        <span class="text-white/80 text-xs font-medium">Research Papers</span>
                    </div>
                    <div class="py-5 text-center bg-white">
                        <a href="#" class="text-gray-500 hover:text-gray-800 text-sm font-semibold transition-colors">
                            View All Projects →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Research Impact Section -->
    <section class="py-24 relative overflow-hidden" style="background-image: linear-gradient(180deg, rgba(14, 58, 38, 0.9) 0%, rgba(10, 44, 28, 0.92) 100%), url('{{ asset('images/ndmu-optimized.jpg') }}'); background-size: cover; background-position: center; background-attachment: fixed;">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center flex flex-col items-center">
            <span class="text-sm font-bold tracking-widest text-[#d69f24] uppercase mb-2 block">By the Numbers</span>
            <h2 class="text-4xl md:text-5xl font-heading font-bold text-white mb-4">Research Impact</h2>
            <p class="text-gray-300 text-lg mb-16 max-w-2xl font-light">Our impact in research excellence across the Philippines</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 w-full max-w-5xl">
                <!-- Card 1 -->
                <div class="glass-card rounded-2xl p-8 text-center transform hover:-translate-y-1 transition-transform duration-300">
                    <div class="w-14 h-14 mx-auto rounded-full bg-white/10 flex items-center justify-center mb-6">
                        <i class="ph ph-users text-3xl text-[#f8b803]"></i>
                    </div>
                    <h3 class="text-4xl font-heading font-bold text-white mb-2">500+</h3>
                    <p class="text-sm text-gray-300 font-medium">Active Researchers</p>
                </div>
                <!-- Card 2 -->
                <div class="glass-card rounded-2xl p-8 text-center transform hover:-translate-y-1 transition-transform duration-300">
                    <div class="w-14 h-14 mx-auto rounded-full bg-white/10 flex items-center justify-center mb-6">
                        <i class="ph ph-book-open-text text-3xl text-[#f8b803]"></i>
                    </div>
                    <h3 class="text-4xl font-heading font-bold text-white mb-2">1,200+</h3>
                    <p class="text-sm text-gray-300 font-medium">Published Papers</p>
                </div>
                <!-- Card 3 -->
                <div class="glass-card rounded-2xl p-8 text-center transform hover:-translate-y-1 transition-transform duration-300">
                    <div class="w-14 h-14 mx-auto rounded-full bg-white/10 flex items-center justify-center mb-6">
                        <i class="ph ph-medal text-3xl text-[#f8b803]"></i>
                    </div>
                    <h3 class="text-4xl font-heading font-bold text-white mb-2">150+</h3>
                    <p class="text-sm text-gray-300 font-medium">International Awards</p>
                </div>
                <!-- Card 4 -->
                <div class="glass-card rounded-2xl p-8 text-center transform hover:-translate-y-1 transition-transform duration-300">
                    <div class="w-14 h-14 mx-auto rounded-full bg-white/10 flex items-center justify-center mb-6">
                        <i class="ph ph-shield-check text-3xl text-[#f8b803]"></i>
                    </div>
                    <h3 class="text-4xl font-heading font-bold text-white mb-2">2,500+</h3>
                    <p class="text-sm text-gray-300 font-medium">Completed Defenses</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-sm font-bold tracking-widest text-[#d69f24] uppercase mb-2 block">Testimonials</span>
                <h2 class="text-4xl md:text-5xl font-heading font-bold text-[#0e5c3a] mb-4">What Our Community Says</h2>
                <p class="text-gray-500 text-lg max-w-3xl mx-auto font-light">Voices from our researchers, advisers, and faculty</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition-shadow duration-300">
                    <div>
                        <!-- Stars -->
                        <div class="flex gap-1 mb-4 text-[#f8b803] text-lg">
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                        </div>
                        <p class="text-gray-600 italic text-sm leading-relaxed mb-6">
                            "The research management system streamlined our entire research process. The platform is intuitive, efficient, and beautifully designed."
                        </p>
                    </div>
                    <div class="flex items-center gap-3 pt-4 border-t border-gray-50">
                        <div class="w-10 h-10 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-sm">
                            M
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800">Maria Santos</h4>
                            <p class="text-xs text-gray-400 font-medium">Student Researcher</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition-shadow duration-300">
                    <div>
                        <div class="flex gap-1 mb-4 text-[#f8b803] text-lg">
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                        </div>
                        <p class="text-gray-600 italic text-sm leading-relaxed mb-6">
                            "As an adviser, I can easily monitor my students' progress and provide timely feedback through the system. Excellent platform!"
                        </p>
                    </div>
                    <div class="flex items-center gap-3 pt-4 border-t border-gray-50">
                        <div class="w-10 h-10 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-sm">
                            R
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800">Dr. Roberto Garcia</h4>
                            <p class="text-xs text-gray-400 font-medium">Research Adviser</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between hover:shadow-md transition-shadow duration-300">
                    <div>
                        <div class="flex gap-1 mb-4 text-[#f8b803] text-lg">
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                            <i class="ph ph-star-fill"></i>
                        </div>
                        <p class="text-gray-600 italic text-sm leading-relaxed mb-6">
                            "The evaluation and grading system is professional and comprehensive. It makes our work as panelists much more organized."
                        </p>
                    </div>
                    <div class="flex items-center gap-3 pt-4 border-t border-gray-50">
                        <div class="w-10 h-10 rounded-full bg-[#0e5c3a] text-white font-bold flex items-center justify-center text-sm">
                            P
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-800">Prof. Patricia Cruz</h4>
                            <p class="text-xs text-gray-400 font-medium">Panelist</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer id="contact" data-scroll-section class="bg-[#052315] text-gray-300 pt-20 pb-8 border-t border-[#0e5c3a]/20">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <!-- Top Footer Content -->
            <div class="flex flex-col lg:flex-row justify-between items-start gap-12 mb-16">
                <!-- Left: Logo, Description & Social Icons -->
                <div class="space-y-6 max-w-xl">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" loading="lazy" decoding="async" class="h-12 w-auto">
                        <div class="flex flex-col leading-none">
                            <span class="font-heading font-extrabold text-2xl text-white tracking-tight">NDMU</span>
                            <span class="text-[10px] font-bold text-[#eebc3f] tracking-wider uppercase mt-1">Research Management System</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-400 leading-relaxed font-light">
                        The official research repository and management portal of Notre Dame of Marbel University. Dedicated to advancing knowledge, fostering innovation, and archiving scholarly achievements.
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <a href="#" class="w-11 h-11 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-[#eebc3f] hover:text-[#052315] hover:border-transparent transition-all duration-300 shadow-md">
                            <i class="ph ph-facebook-logo text-xl"></i>
                        </a>
                        <a href="#" class="w-11 h-11 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-[#eebc3f] hover:text-[#052315] hover:border-transparent transition-all duration-300 shadow-md">
                            <i class="ph ph-instagram-logo text-xl"></i>
                        </a>
                        <a href="#" class="w-11 h-11 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-[#eebc3f] hover:text-[#052315] hover:border-transparent transition-all duration-300 shadow-md">
                            <i class="ph ph-youtube-logo text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Right: Call to Action / Info -->
                <div class="bg-white/5 border border-white/10 rounded-3xl p-6 lg:p-8 max-w-md w-full">
                    <span class="text-xs font-bold text-[#eebc3f] uppercase tracking-widest block mb-2">NDMU Portal</span>
                    <h4 class="text-white font-bold font-heading text-xl mb-3">Empowering Research</h4>
                    <p class="text-xs text-gray-400 leading-relaxed mb-4 font-light">
                        Access guidelines, download templates, and start archiving your scholarly work inside our secure, modular monolith ecosystem.
                    </p>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#eebc3f] text-[#052315] text-xs font-bold rounded-xl hover:bg-[#d69f24] transition-colors">
                        Access Portal <i class="ph ph-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Horizontal Separator -->
            <div class="border-t border-white/10 my-10"></div>

            <!-- Middle Row: Contact Info Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
                <!-- Location -->
                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 flex items-start gap-4 hover:bg-white/10 hover:border-white/20 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-[#eebc3f]/10 flex items-center justify-center text-[#eebc3f] flex-shrink-0">
                        <i class="ph ph-map-pin text-xl"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Our Location</span>
                        <span class="text-sm text-gray-200">Alunan Avenue, Koronadal City, South Cotabato, Philippines</span>
                    </div>
                </div>

                <!-- Phone -->
                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 flex items-start gap-4 hover:bg-white/10 hover:border-white/20 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-[#eebc3f]/10 flex items-center justify-center text-[#eebc3f] flex-shrink-0">
                        <i class="ph ph-phone text-xl"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Call Us</span>
                        <span class="text-sm text-gray-200">(083) 228-3167</span>
                    </div>
                </div>

                <!-- Email -->
                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 flex items-start gap-4 hover:bg-white/10 hover:border-white/20 transition-all duration-300">
                    <div class="w-11 h-11 rounded-xl bg-[#eebc3f]/10 flex items-center justify-center text-[#eebc3f] flex-shrink-0">
                        <i class="ph ph-envelope-simple text-xl"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Email Us</span>
                        <span class="text-sm text-gray-200">research@ndmu.edu.ph</span>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright Row -->
            <div class="pt-8 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500 font-medium">
                <p>© 2026 Notre Dame of Marbel University. All rights reserved.</p>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-gray-300 transition">Privacy Policy</a>
                    <span>·</span>
                    <a href="#" class="hover:text-gray-300 transition">Terms of Use</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
