@extends('layouts.auth')

@section('auth-content')
<div class="h-screen w-screen overflow-hidden flex flex-col md:flex-row relative bg-[#F7FAF8]">
    <!-- Left Side: Premium NDMU Research Brand Panel -->
    <div class="w-full md:w-[50%] lg:w-[48%] bg-gradient-to-br from-[#003D29] via-[#005337] to-[#00462F] text-white p-7 lg:p-12 xl:p-14 flex flex-col justify-between relative h-full overflow-hidden shrink-0">
        <!-- Subtle academic background patterns -->
        <svg class="absolute inset-0 w-full h-full opacity-5 pointer-events-none" aria-hidden="true">
            <pattern id="dots-login-stretched" x="0" y="0" width="28" height="28" patternUnits="userSpaceOnUse">
                <circle cx="2" cy="2" r="1.4" fill="white" />
            </pattern>
            <rect width="100%" height="100%" fill="url(#dots-login-stretched)" />
        </svg>

        <!-- Decorative background glow & circles -->
        <div class="absolute -top-36 -right-32 w-96 h-96 rounded-full border border-white/10 pointer-events-none"></div>
        <div class="absolute -bottom-36 -left-36 w-88 h-88 rounded-full border border-white/10 pointer-events-none"></div>

        <!-- Header / Logo -->
        <div class="relative z-10 flex items-center gap-3.5 animate-fade-in-left">
            <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-11 lg:h-13 w-auto object-contain">
            <div class="flex flex-col leading-none border-l-2 border-[#E5B72E] pl-3.5">
                <span class="font-heading font-extrabold text-2xl lg:text-3xl text-white tracking-wider">NDMU</span>
                <span class="text-[9px] lg:text-[10px] font-extrabold text-[#E5B72E] tracking-[0.25em] uppercase mt-0.5">Research Management</span>
            </div>
        </div>

        <!-- Main Message & Timeline -->
        <div class="relative z-10 my-auto py-2 lg:py-4 space-y-5 lg:space-y-6 animate-fade-in-left" style="animation-delay: 100ms;">
            <div class="space-y-2.5">
                <span class="text-xs lg:text-sm font-extrabold tracking-[0.3em] text-[#E5B72E] uppercase block">Welcome Back</span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-[3.5rem] font-serif font-bold text-white leading-[1.12] tracking-wide">
                    Research.<br>
                    <span class="tracking-wide">Collaborate.</span><br>
                    <span class="text-[#E5B72E] tracking-wide">Achieve More.</span>
                </h1>
            </div>

            <!-- Supporting Text -->
            <p class="text-white/90 text-sm lg:text-base leading-relaxed max-w-xl font-normal tracking-wide">
                A centralized research workspace connecting students, faculty, advisers, and panelists throughout the academic research journey.
            </p>

            <!-- Research Journey Timeline -->
            <div class="space-y-3.5 lg:space-y-4 pt-1">
                <!-- Stage 1: Submit -->
                <div class="flex gap-4 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-2xl bg-white/20 border border-white/35 flex items-center justify-center shrink-0 text-[#E5B72E] text-lg lg:text-xl shadow-sm">
                            <i class="ph-bold ph-file-arrow-up"></i>
                        </div>
                        <div class="w-0.5 h-4 lg:h-5 bg-gradient-to-b from-[#E5B72E]/40 to-transparent mt-1"></div>
                    </div>
                    <div class="pt-0.5">
                        <h3 class="text-sm lg:text-base font-extrabold text-white tracking-wide">Submit</h3>
                        <p class="text-[11px] lg:text-xs text-white/75 tracking-wide">Upload and manage research proposals</p>
                    </div>
                </div>

                <!-- Stage 2: Collaborate -->
                <div class="flex gap-4 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-2xl bg-white/20 border border-white/35 flex items-center justify-center shrink-0 text-[#E5B72E] text-lg lg:text-xl shadow-sm">
                            <i class="ph-bold ph-users-three"></i>
                        </div>
                        <div class="w-0.5 h-4 lg:h-5 bg-gradient-to-b from-[#E5B72E]/40 to-transparent mt-1"></div>
                    </div>
                    <div class="pt-0.5">
                        <h3 class="text-sm lg:text-base font-extrabold text-white tracking-wide">Collaborate</h3>
                        <p class="text-[11px] lg:text-xs text-white/75 tracking-wide">Work with advisers and panelists</p>
                    </div>
                </div>

                <!-- Stage 3: Review -->
                <div class="flex gap-4 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-2xl bg-white/20 border border-white/35 flex items-center justify-center shrink-0 text-[#E5B72E] text-lg lg:text-xl shadow-sm">
                            <i class="ph-bold ph-file-text"></i>
                        </div>
                        <div class="w-0.5 h-4 lg:h-5 bg-gradient-to-b from-[#E5B72E]/40 to-transparent mt-1"></div>
                    </div>
                    <div class="pt-0.5">
                        <h3 class="text-sm lg:text-base font-extrabold text-white tracking-wide">Review</h3>
                        <p class="text-[11px] lg:text-xs text-white/75 tracking-wide">Track feedback and research progress</p>
                    </div>
                </div>

                <!-- Stage 4: Defend -->
                <div class="flex gap-4 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-2xl bg-white/20 border border-white/35 flex items-center justify-center shrink-0 text-[#E5B72E] text-lg lg:text-xl shadow-sm">
                            <i class="ph-bold ph-presentation-chart"></i>
                        </div>
                    </div>
                    <div class="pt-0.5">
                        <h3 class="text-sm lg:text-base font-extrabold text-white tracking-wide">Defend</h3>
                        <p class="text-[11px] lg:text-xs text-white/75 tracking-wide">Manage research defense schedules</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 flex items-center gap-2.5 text-xs lg:text-sm text-white/80 font-semibold tracking-wide animate-fade-in-left" style="animation-delay: 200ms;">
            <i class="ph-bold ph-shield-check text-[#E5B72E] text-base"></i>
            <span class="tracking-wide">Secure Academic Research Portal</span>
        </div>
    </div>

    <!-- Right Side: Strictly NON-SCROLLABLE (overflow-hidden) with Floating Card -->
    <div class="w-full md:w-[50%] lg:w-[52%] flex items-center justify-center p-4 sm:p-6 lg:p-8 relative h-full overflow-hidden bg-[#F7FAF8]">
<<<<<<< HEAD
        <!-- Abstract Topographic Contour Lines — white right panel ONLY, matching reference image -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 750 900" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <g stroke="#222" stroke-linecap="round" stroke-linejoin="round" fill="none">

                <!-- LEFT SIDE: two large S-wave columns (most prominent in reference) -->
                <!-- Outer left S-curve -->
                <path d="M -30 120 C 30 80, 80 150, 60 240 C 40 330, -10 390, 20 470 C 50 550, 110 590, 90 670 C 70 750, 10 800, -30 860"
                      stroke-width="1.5" opacity="0.35"/>
                <!-- Inner left S-curve (offset inward) -->
                <path d="M 30 140 C 80 100, 130 170, 110 260 C 90 350, 40 400, 70 490 C 100 580, 160 610, 140 700 C 120 780, 50 830, 20 900"
                      stroke-width="1.4" opacity="0.28"/>

                <!-- UPPER-LEFT blob / kidney shape top-left corner -->
                <path d="M -30 -10 C 30 -30, 120 10, 170 70 C 220 130, 200 210, 140 240 C 80 270, 10 230, -20 170 C -50 110, -30 20, -30 -10"
                      stroke-width="1.4" opacity="0.30"/>

                <!-- UPPER-CENTER arc: gentle wave across top -->
                <path d="M 200 -30 C 280 20, 340 -20, 420 30 C 500 80, 530 30, 620 -10 C 700 -50, 750 10, 780 -20"
                      stroke-width="1.3" opacity="0.27"/>
                <path d="M 250 30 C 320 -10, 380 40, 460 10 C 540 -20, 600 30, 680 0"
                      stroke-width="1.2" opacity="0.20"/>

                <!-- UPPER-RIGHT area: organic loop/lobe -->
                <path d="M 660 -30 C 720 10, 790 40, 800 110 C 810 180, 760 230, 700 220 C 640 210, 620 160, 650 100 C 680 40, 700 10, 660 -30"
                      stroke-width="1.4" opacity="0.30"/>
                <!-- Tail line from upper-right going down right edge -->
                <path d="M 790 200 C 770 260, 800 310, 790 370 C 780 430, 740 460, 760 520"
                      stroke-width="1.3" opacity="0.27"/>

                <!-- RIGHT SIDE mid-height S-curves -->
                <path d="M 800 430 C 740 390, 700 450, 720 540 C 740 630, 800 670, 790 760 C 780 840, 730 880, 760 940"
                      stroke-width="1.4" opacity="0.30"/>
                <path d="M 760 460 C 710 420, 680 490, 700 570 C 720 650, 775 690, 770 770 C 765 840, 720 870, 740 930"
                      stroke-width="1.3" opacity="0.22"/>

                <!-- LOWER-LEFT swirl / flow -->
                <path d="M -30 780 C 40 740, 130 770, 180 840 C 230 910, 200 970, 120 990 C 40 1010, -20 960, -30 900"
                      stroke-width="1.4" opacity="0.30"/>
                <path d="M 30 860 C 90 820, 170 850, 220 920 C 270 990, 240 1040, 160 1050"
                      stroke-width="1.2" opacity="0.22"/>

                <!-- LOWER-CENTER gentle wave -->
                <path d="M 250 920 C 330 880, 420 930, 500 900 C 580 870, 620 920, 680 900"
                      stroke-width="1.3" opacity="0.25"/>

                <!-- LOWER-RIGHT lobe -->
                <path d="M 680 870 C 720 830, 790 850, 810 910 C 830 970, 790 1010, 740 1000 C 690 990, 670 950, 680 870"
                      stroke-width="1.3" opacity="0.27"/>

            </g>
=======
        <!-- Subtle background graphics -->
        <svg class="absolute inset-0 w-full h-full opacity-30 pointer-events-none" viewBox="0 0 600 800" aria-hidden="true">
            <defs>
                <pattern id="dots-auth-clean-str" x="0" y="0" width="30" height="30" patternUnits="userSpaceOnUse">
                    <circle cx="1.5" cy="1.5" r="0.8" fill="#087443" opacity="0.1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots-auth-clean-str)" />
            <circle cx="500" cy="100" r="220" fill="#087443" opacity="0.03" />
            <circle cx="100" cy="600" r="180" fill="#087443" opacity="0.04" />
>>>>>>> 8b15011507c76d76c221e8be36e9a204fbd67a03
        </svg>

        <!-- High-Impact Close Button (Top Right) -->
        <a href="{{ url('/') }}" class="absolute top-5 right-5 group flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#003D29] text-white border border-[#00633E] shadow-xl shadow-[#003D29]/25 hover:bg-[#E5B72E] hover:text-[#002E1F] hover:border-[#E5B72E] hover:scale-105 transition-all duration-300 z-30" title="Back to Home">
            <span class="text-xs font-extrabold tracking-wide">Back to Home</span>
            <div class="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center group-hover:bg-[#002E1F]/20 group-hover:rotate-90 transition-all duration-300">
                <i class="ph-bold ph-x text-sm"></i>
            </div>
        </a>

        <!-- Floating Authentication Card -->
        <div class="relative w-full max-w-[460px] sm:max-w-[480px] lg:max-w-[500px] my-auto animate-fade-in-up">
            <!-- Floating Ambient Back Glow -->
            <div class="absolute -inset-3 rounded-[38px] bg-gradient-to-tr from-[#003D29]/30 via-[#E5B72E]/20 to-[#005337]/30 blur-2xl opacity-75 pointer-events-none"></div>

            <!-- Floating Main Card Body -->
            <div class="relative bg-white rounded-3xl shadow-[0_20px_70px_-15px_rgba(0,61,41,0.32),0_10px_25px_-5px_rgba(0,0,0,0.1)] border border-[#CFE3D8]/80 p-6 sm:p-7 lg:p-8 flex flex-col overflow-hidden transition-all duration-500 hover:shadow-[0_30px_85px_-15px_rgba(0,61,41,0.4)]">
                <!-- Card top accent -->
                <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full bg-gradient-to-br from-[#087443]/10 via-[#087443]/5 to-transparent pointer-events-none"></div>

                <!-- Tabs -->
                <div class="flex bg-[#F1F7F4] p-1.5 rounded-2xl mb-5 relative z-10 border border-[#DDE5E1]/70 shadow-inner">
                    <a href="{{ route('login') }}" class="flex-1 flex items-center justify-center gap-2 py-2.5 text-xs sm:text-sm font-bold rounded-xl bg-[#00633E] text-white shadow-md shadow-[#00633E]/20 transition-all duration-200">
                        <i class="ph-bold ph-sign-in text-base"></i> Sign In
                    </a>
                    <a href="{{ route('register') }}" class="flex-1 flex items-center justify-center gap-2 py-2.5 text-xs sm:text-sm font-bold rounded-xl text-[#526359] hover:text-[#00633E] transition-all duration-200">
                        <i class="ph-bold ph-user-plus text-base"></i> Register
                    </a>
                </div>

                <!-- Header Icon & Title -->
                <div class="text-center mb-5 flex flex-col items-center relative z-10">
                    <div class="w-13 h-13 rounded-2xl bg-emerald-50/80 border border-[#CFE3D8] text-[#00633E] flex items-center justify-center mb-2.5 text-2xl shadow-sm transition-transform duration-300 hover:scale-105">
                        <i class="ph-bold ph-shield-check"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-[#0f291e] tracking-tight">Welcome Back</h2>
                    <p class="text-xs sm:text-sm text-[#526359] font-medium mt-0.5">Continue to your research workspace.</p>
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('login.store') }}" class="space-y-3.5 mb-4 relative z-10">
                    @csrf

                    @if (session('status'))
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-xs sm:text-sm font-semibold text-emerald-800 animate-slide-down" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-3 text-xs sm:text-sm font-semibold text-red-700 animate-slide-down" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <!-- Email Field -->
                    <div class="space-y-1.5">
                        <label for="email" class="text-xs sm:text-sm font-bold text-[#0f291e] block">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#00633E] pointer-events-none">
                                <i class="ph-bold ph-envelope-simple text-base sm:text-lg"></i>
                            </span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                required
                                placeholder="your.email@ndmu.edu.ph"
                                class="w-full pl-11 pr-4 py-2.5 bg-white border border-[#DDE5E1] rounded-2xl text-xs sm:text-sm font-medium text-[#0f291e] placeholder-[#8B9690] focus:outline-none focus:border-[#00633E] focus:ring-4 focus:ring-[#00633E]/10 transition-all duration-200"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="password" class="text-xs sm:text-sm font-bold text-[#0f291e] block">Password</label>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#00633E] pointer-events-none">
                                <i class="ph-bold ph-lock text-base sm:text-lg"></i>
                            </span>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                placeholder="Enter your password"
                                class="w-full pl-11 pr-11 py-2.5 bg-white border border-[#DDE5E1] rounded-2xl text-xs sm:text-sm font-medium text-[#0f291e] placeholder-[#8B9690] focus:outline-none focus:border-[#00633E] focus:ring-4 focus:ring-[#00633E]/10 transition-all duration-200"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-[#68766F] hover:text-[#00633E] transition-colors duration-200"
                                data-password-toggle
                                data-password-input="password"
                                aria-label="Show password"
                                aria-pressed="false"
                            >
                                <i data-password-show-icon class="ph-bold ph-eye text-base sm:text-lg" aria-hidden="true"></i>
                                <i data-password-hide-icon class="ph-bold ph-eye-slash text-base sm:text-lg hidden" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between gap-4 text-xs sm:text-sm pt-0.5">
                        <label class="flex items-center gap-2 text-[#526359] cursor-pointer hover:text-[#0f291e] transition-colors font-medium">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-[#DDE5E1] text-[#00633E] focus:ring-[#00633E]">
                            <span>Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="font-bold text-[#00633E] hover:underline transition-colors">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-3 bg-[#00633E] hover:bg-[#004F32] text-white text-xs sm:text-sm font-extrabold rounded-2xl flex items-center justify-center gap-2 shadow-md shadow-[#00633E]/20 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 active:translate-y-0 mt-1">
                        <span>Sign In</span>
                        <i class="ph-bold ph-arrow-right text-base"></i>
                    </button>
                </form>

                <!-- Staff Notice -->
                <div class="border border-[#CFE3D8] bg-[#EAF5EF] rounded-2xl p-3 relative z-10">
                    <div class="flex gap-2.5 items-start">
                        <i class="ph-bold ph-shield-check text-[#00633E] shrink-0 text-base mt-0.5"></i>
                        <div class="text-[11px] sm:text-xs text-[#0f291e] space-y-0.5">
                            <p class="font-bold">Staff Account Access</p>
                            <p class="text-[#526359] leading-snug">Faculty and staff accounts are managed by the Research Office. <a href="mailto:research@ndmu.edu.ph" class="font-bold text-[#00633E] hover:underline">Contact Research Office</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- High-Impact Floating Help Button (Bottom Right) -->
    <a href="mailto:research@ndmu.edu.ph" class="absolute bottom-5 right-5 group flex items-center gap-2 px-4 py-2.5 rounded-full bg-[#003D29] text-white border border-[#00633E] shadow-xl shadow-[#003D29]/25 hover:bg-[#E5B72E] hover:text-[#002E1F] hover:border-[#E5B72E] hover:scale-105 transition-all duration-300 z-30" title="Get Support">
        <div class="w-6 h-6 rounded-full bg-white/15 flex items-center justify-center group-hover:bg-[#002E1F]/20 transition-all duration-300">
            <i class="ph-bold ph-question text-sm"></i>
        </div>
        <span class="text-xs font-extrabold tracking-wide">Need Help?</span>
    </a>
</div>
@endsection
