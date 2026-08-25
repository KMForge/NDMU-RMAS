@extends('layouts.auth')

@section('auth-content')
<div class="h-screen w-screen overflow-hidden flex flex-col md:flex-row relative bg-[#F7FAF8]">
    <!-- Left Side: Premium NDMU Research Brand Panel -->
    <div class="w-full md:w-[45%] lg:w-[42%] bg-gradient-to-br from-[#003D29] via-[#005337] to-[#00462F] text-white p-8 md:p-12 lg:p-16 flex flex-col justify-between relative h-full overflow-hidden shrink-0">
        <!-- Subtle academic patterns -->
        <svg class="absolute inset-0 w-full h-full opacity-5 pointer-events-none" aria-hidden="true">
            <pattern id="dots-login-bg" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
                <circle cx="2" cy="2" r="1.2" fill="white" />
            </pattern>
            <rect width="100%" height="100%" fill="url(#dots-login-bg)" />
        </svg>

        <!-- Decorative subtle accent circles -->
        <div class="absolute -top-36 -right-32 w-96 h-96 rounded-full border border-white/10 pointer-events-none"></div>
        <div class="absolute -bottom-36 -left-36 w-88 h-88 rounded-full border border-white/10 pointer-events-none"></div>

        <!-- Header / Logo -->
        <div class="relative z-10 flex items-center gap-3.5 animate-fade-in-left">
            <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-12 lg:h-14 w-auto object-contain">
            <div class="flex flex-col leading-none border-l-2 border-[#E5B72E] pl-3.5">
                <span class="font-heading font-extrabold text-2xl lg:text-3xl text-white tracking-tight">NDMU</span>
                <span class="text-[10px] lg:text-xs font-bold text-[#E5B72E] tracking-widest uppercase mt-1">Research Management</span>
            </div>
        </div>

        <!-- Main Message & Timeline: Expanded to gracefully occupy space -->
        <div class="relative z-10 my-auto py-6 lg:py-10 space-y-8 lg:space-y-10 animate-fade-in-left" style="animation-delay: 100ms;">
            <div class="space-y-3">
                <span class="text-xs font-extrabold tracking-widest text-[#E5B72E] uppercase block">Welcome Back</span>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-serif font-bold text-white leading-tight tracking-tight">
                    Research.<br>
                    <span>Collaborate.</span><br>
                    <span class="text-[#E5B72E]">Achieve More.</span>
                </h1>
            </div>

            <!-- Supporting Text: Expanded -->
            <p class="text-white/85 text-sm md:text-base leading-relaxed max-w-md font-light">
                A centralized research workspace connecting students, faculty, advisers, and panelists throughout the academic research journey.
            </p>

            <!-- Research Journey Timeline: Occupying vertical space cleanly -->
            <div class="space-y-5 md:space-y-6 pt-2">
                <!-- Stage 1: Submit -->
                <div class="flex gap-4.5 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-11 h-11 rounded-2xl bg-white/15 border border-white/30 flex items-center justify-center shrink-0 text-[#E5B72E] shadow-sm">
                            <i class="ph-bold ph-file-arrow-up text-xl"></i>
                        </div>
                        <div class="w-0.5 h-6 lg:h-8 bg-gradient-to-b from-[#E5B72E]/40 to-transparent mt-1.5"></div>
                    </div>
                    <div class="pt-1">
                        <h3 class="text-base font-bold text-white">Submit</h3>
                        <p class="text-xs md:text-sm text-white/70 mt-0.5">Upload and manage research proposals</p>
                    </div>
                </div>

                <!-- Stage 2: Collaborate -->
                <div class="flex gap-4.5 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-11 h-11 rounded-2xl bg-white/15 border border-white/30 flex items-center justify-center shrink-0 text-[#E5B72E] shadow-sm">
                            <i class="ph-bold ph-users-three text-xl"></i>
                        </div>
                        <div class="w-0.5 h-6 lg:h-8 bg-gradient-to-b from-[#E5B72E]/40 to-transparent mt-1.5"></div>
                    </div>
                    <div class="pt-1">
                        <h3 class="text-base font-bold text-white">Collaborate</h3>
                        <p class="text-xs md:text-sm text-white/70 mt-0.5">Work with advisers and panelists</p>
                    </div>
                </div>

                <!-- Stage 3: Review -->
                <div class="flex gap-4.5 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-11 h-11 rounded-2xl bg-white/15 border border-white/30 flex items-center justify-center shrink-0 text-[#E5B72E] shadow-sm">
                            <i class="ph-bold ph-file-text text-xl"></i>
                        </div>
                        <div class="w-0.5 h-6 lg:h-8 bg-gradient-to-b from-[#E5B72E]/40 to-transparent mt-1.5"></div>
                    </div>
                    <div class="pt-1">
                        <h3 class="text-base font-bold text-white">Review</h3>
                        <p class="text-xs md:text-sm text-white/70 mt-0.5">Track feedback and research progress</p>
                    </div>
                </div>

                <!-- Stage 4: Defend -->
                <div class="flex gap-4.5 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-11 h-11 rounded-2xl bg-white/15 border border-white/30 flex items-center justify-center shrink-0 text-[#E5B72E] shadow-sm">
                            <i class="ph-bold ph-presentation-chart text-xl"></i>
                        </div>
                    </div>
                    <div class="pt-1">
                        <h3 class="text-base font-bold text-white">Defend</h3>
                        <p class="text-xs md:text-sm text-white/70 mt-0.5">Manage research defense schedules</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 flex items-center gap-2.5 text-xs md:text-sm text-white/70 font-medium animate-fade-in-left" style="animation-delay: 200ms;">
            <i class="ph-bold ph-shield-check text-[#E5B72E] text-base"></i>
            <span>Secure Academic Research Portal</span>
        </div>
    </div>

    <!-- Right Side: Premium Authentication Area with Bigger Card & Floating Shadow -->
    <div class="w-full md:w-[55%] lg:w-[58%] flex items-center justify-center p-6 md:p-12 lg:p-16 relative h-full overflow-y-auto bg-[#F7FAF8]">
        <!-- Subtle background graphics -->
        <svg class="absolute inset-0 w-full h-full opacity-35 pointer-events-none" viewBox="0 0 600 800" aria-hidden="true">
            <defs>
                <pattern id="dots-auth-bg-lg" x="0" y="0" width="32" height="32" patternUnits="userSpaceOnUse">
                    <circle cx="1.5" cy="1.5" r="0.8" fill="#087443" opacity="0.1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots-auth-bg-lg)" />
            <circle cx="500" cy="100" r="220" fill="#087443" opacity="0.03" />
            <circle cx="100" cy="600" r="180" fill="#087443" opacity="0.04" />
        </svg>

        <!-- Close Button -->
        <a href="{{ url('/') }}" class="absolute top-6 right-6 w-11 h-11 rounded-full bg-white border border-[#DDE5E1] flex items-center justify-center text-[#68766F] hover:text-[#00633E] hover:bg-[#F1F7F4] shadow-md transition-all duration-300 z-30 hover:-rotate-90" title="Back to home">
            <i class="ph-bold ph-x text-lg"></i>
        </a>

        <!-- Bigger Authentication Card Container with Floating Backdrop Glow & Shadow -->
        <div class="relative w-full max-w-[560px] md:max-w-[580px] my-auto animate-fade-in-up">
            <!-- Floating Ambient Background Glow -->
            <div class="absolute -inset-3 rounded-[40px] bg-gradient-to-tr from-[#003D29]/25 via-[#E5B72E]/15 to-[#005337]/25 blur-2xl opacity-75 pointer-events-none"></div>

            <!-- Main Floating Card -->
            <div class="relative bg-white rounded-3xl shadow-[0_30px_90px_-15px_rgba(0,61,41,0.3),0_15px_35px_-10px_rgba(0,0,0,0.1)] border border-[#CFE3D8]/80 p-8 sm:p-10 md:p-12 flex flex-col overflow-hidden transition-all duration-500 hover:shadow-[0_40px_100px_-15px_rgba(0,61,41,0.38)] md:hover:-translate-y-1">
                <!-- Card top geometric accent -->
                <div class="absolute -top-12 -right-12 w-36 h-36 rounded-full bg-gradient-to-br from-[#087443]/10 via-[#087443]/5 to-transparent pointer-events-none"></div>

                <!-- Segmented Control / Tabs -->
                <div class="flex bg-[#F1F7F4] p-1.5 rounded-2xl mb-8 relative z-10 border border-[#DDE5E1]/70 shadow-inner">
                    <a href="{{ route('login') }}" class="flex-1 flex items-center justify-center gap-2 py-3 text-xs md:text-sm font-bold rounded-xl bg-[#00633E] text-white shadow-md shadow-[#00633E]/20 transition-all duration-300">
                        <i class="ph-bold ph-sign-in text-base"></i> Sign In
                    </a>
                    <a href="{{ route('register') }}" class="flex-1 flex items-center justify-center gap-2 py-3 text-xs md:text-sm font-bold rounded-xl text-[#526359] hover:text-[#00633E] transition-all duration-300">
                        <i class="ph-bold ph-user-plus text-base"></i> Register
                    </a>
                </div>

                <!-- Header Icon & Title -->
                <div class="text-center mb-8 flex flex-col items-center relative z-10">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-50/80 border border-[#CFE3D8] text-[#00633E] flex items-center justify-center mb-4 text-3xl shadow-sm transition-transform duration-300 hover:scale-105">
                        <i class="ph-bold ph-shield-check"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-[#0f291e] tracking-tight">Welcome Back</h2>
                    <p class="text-sm text-[#526359] font-medium mt-1">Continue to your research workspace.</p>
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('login.store') }}" class="space-y-5 mb-6 relative z-10">
                    @csrf

                    @if (session('status'))
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs sm:text-sm font-semibold text-emerald-800 animate-slide-down" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-xs sm:text-sm font-semibold text-red-700 animate-slide-down" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label for="email" class="text-xs sm:text-sm font-bold text-[#0f291e] block">Email Address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#00633E] pointer-events-none">
                                <i class="ph-bold ph-envelope-simple text-lg"></i>
                            </span>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                required
                                placeholder="your.email@ndmu.edu.ph"
                                class="w-full pl-12 pr-4 py-3.5 bg-white border border-[#DDE5E1] rounded-2xl text-sm font-medium text-[#0f291e] placeholder-[#8B9690] focus:outline-none focus:border-[#00633E] focus:ring-4 focus:ring-[#00633E]/10 transition-all duration-300"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="password" class="text-xs sm:text-sm font-bold text-[#0f291e] block">Password</label>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#00633E] pointer-events-none">
                                <i class="ph-bold ph-lock text-lg"></i>
                            </span>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                placeholder="Enter your password"
                                class="w-full pl-12 pr-12 py-3.5 bg-white border border-[#DDE5E1] rounded-2xl text-sm font-medium text-[#0f291e] placeholder-[#8B9690] focus:outline-none focus:border-[#00633E] focus:ring-4 focus:ring-[#00633E]/10 transition-all duration-300"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#68766F] hover:text-[#00633E] transition-colors duration-300"
                                data-password-toggle
                                data-password-input="password"
                                aria-label="Show password"
                                aria-pressed="false"
                            >
                                <i data-password-show-icon class="ph-bold ph-eye text-lg" aria-hidden="true"></i>
                                <i data-password-hide-icon class="ph-bold ph-eye-slash text-lg hidden" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between gap-4 text-xs sm:text-sm pt-1">
                        <label class="flex items-center gap-2 text-[#526359] cursor-pointer hover:text-[#0f291e] transition-colors font-medium">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-[#DDE5E1] text-[#00633E] focus:ring-[#00633E] h-4 w-4">
                            <span>Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="font-bold text-[#00633E] hover:underline transition-colors">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-4 bg-[#00633E] hover:bg-[#004F32] text-white text-sm font-extrabold rounded-2xl flex items-center justify-center gap-2.5 shadow-lg shadow-[#00633E]/20 hover:shadow-xl hover:shadow-[#00633E]/30 hover:-translate-y-0.5 transition-all duration-300 active:translate-y-0 mt-2">
                        <span>Sign In</span>
                        <i class="ph-bold ph-arrow-right text-base"></i>
                    </button>
                </form>

                <!-- Staff Account Notice -->
                <div class="border border-[#CFE3D8] bg-[#EAF5EF] rounded-2xl p-4 relative z-10">
                    <div class="flex gap-3 items-start">
                        <i class="ph-bold ph-shield-check text-[#00633E] shrink-0 text-lg mt-0.5"></i>
                        <div class="text-xs text-[#0f291e] space-y-1">
                            <p class="font-bold">Staff Account Access</p>
                            <p class="text-[#526359] leading-relaxed">Faculty and staff accounts are managed by the Research Office. <a href="mailto:research@ndmu.edu.ph" class="font-bold text-[#00633E] hover:underline">Contact Research Office</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Help Button -->
    <a href="#" class="absolute bottom-6 right-6 w-11 h-11 rounded-full bg-white border border-[#DDE5E1] flex items-center justify-center text-[#68766F] hover:text-[#00633E] hover:bg-[#F1F7F4] shadow-lg transition-all duration-300 hover:scale-110 z-30" title="Help">
        <i class="ph-bold ph-question text-lg"></i>
    </a>
</div>
@endsection
