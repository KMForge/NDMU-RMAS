@extends('layouts.auth')

@section('auth-content')
<div class="h-screen w-screen overflow-hidden flex flex-col md:flex-row relative bg-[#F7FAF8]">
    <!-- Left Side: Premium NDMU Research Brand Panel -->
    <div class="w-full md:w-[46%] lg:w-[44%] bg-gradient-to-br from-[#003D29] via-[#005337] to-[#00462F] text-white p-6 lg:p-12 xl:p-14 flex flex-col justify-between relative h-full overflow-hidden shrink-0">
        <!-- Subtle academic background patterns -->
        <svg class="absolute inset-0 w-full h-full opacity-5 pointer-events-none" aria-hidden="true">
            <pattern id="dots-login-perfect" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
                <circle cx="2" cy="2" r="1.2" fill="white" />
            </pattern>
            <rect width="100%" height="100%" fill="url(#dots-login-perfect)" />
        </svg>

        <!-- Decorative background glow & circles -->
        <div class="absolute -top-32 -right-28 w-88 h-88 rounded-full border border-white/10 pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-32 w-80 h-80 rounded-full border border-white/10 pointer-events-none"></div>

        <!-- Header / Logo -->
        <div class="relative z-10 flex items-center gap-3 animate-fade-in-left">
            <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-11 lg:h-13 w-auto object-contain">
            <div class="flex flex-col leading-none border-l-2 border-[#E5B72E] pl-3">
                <span class="font-heading font-extrabold text-xl lg:text-2xl text-white tracking-tight">NDMU</span>
                <span class="text-[9px] lg:text-[10px] font-extrabold text-[#E5B72E] tracking-widest uppercase mt-0.5">Research Management</span>
            </div>
        </div>

        <!-- Main Message & Timeline: Perfectly balanced & filling space without cutoff -->
        <div class="relative z-10 my-auto py-2 lg:py-4 space-y-5 lg:space-y-7 animate-fade-in-left" style="animation-delay: 100ms;">
            <div class="space-y-2">
                <span class="text-xs font-extrabold tracking-widest text-[#E5B72E] uppercase block">Welcome Back</span>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-[3.4rem] font-serif font-bold text-white leading-[1.12] tracking-tight">
                    Research.<br>
                    <span>Collaborate.</span><br>
                    <span class="text-[#E5B72E]">Achieve More.</span>
                </h1>
            </div>

            <!-- Supporting Text -->
            <p class="text-white/85 text-xs sm:text-sm lg:text-base leading-relaxed max-w-md font-normal">
                A centralized research workspace connecting students, faculty, advisers, and panelists throughout the academic research journey.
            </p>

            <!-- Research Journey Timeline: Clean vertical spacing fitting inside height -->
            <div class="space-y-3.5 lg:space-y-4 pt-1">
                <!-- Stage 1: Submit -->
                <div class="flex gap-3.5 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-2xl bg-white/20 border border-white/35 flex items-center justify-center shrink-0 text-[#E5B72E] text-lg lg:text-xl shadow-sm">
                            <i class="ph-bold ph-file-arrow-up"></i>
                        </div>
                        <div class="w-0.5 h-4 lg:h-5 bg-gradient-to-b from-[#E5B72E]/40 to-transparent mt-1"></div>
                    </div>
                    <div class="pt-0.5">
                        <h3 class="text-sm lg:text-base font-extrabold text-white">Submit</h3>
                        <p class="text-[11px] lg:text-xs text-white/75">Upload and manage research proposals</p>
                    </div>
                </div>

                <!-- Stage 2: Collaborate -->
                <div class="flex gap-3.5 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-2xl bg-white/20 border border-white/35 flex items-center justify-center shrink-0 text-[#E5B72E] text-lg lg:text-xl shadow-sm">
                            <i class="ph-bold ph-users-three"></i>
                        </div>
                        <div class="w-0.5 h-4 lg:h-5 bg-gradient-to-b from-[#E5B72E]/40 to-transparent mt-1"></div>
                    </div>
                    <div class="pt-0.5">
                        <h3 class="text-sm lg:text-base font-extrabold text-white">Collaborate</h3>
                        <p class="text-[11px] lg:text-xs text-white/75">Work with advisers and panelists</p>
                    </div>
                </div>

                <!-- Stage 3: Review -->
                <div class="flex gap-3.5 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-2xl bg-white/20 border border-white/35 flex items-center justify-center shrink-0 text-[#E5B72E] text-lg lg:text-xl shadow-sm">
                            <i class="ph-bold ph-file-text"></i>
                        </div>
                        <div class="w-0.5 h-4 lg:h-5 bg-gradient-to-b from-[#E5B72E]/40 to-transparent mt-1"></div>
                    </div>
                    <div class="pt-0.5">
                        <h3 class="text-sm lg:text-base font-extrabold text-white">Review</h3>
                        <p class="text-[11px] lg:text-xs text-white/75">Track feedback and research progress</p>
                    </div>
                </div>

                <!-- Stage 4: Defend -->
                <div class="flex gap-3.5 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-2xl bg-white/20 border border-white/35 flex items-center justify-center shrink-0 text-[#E5B72E] text-lg lg:text-xl shadow-sm">
                            <i class="ph-bold ph-presentation-chart"></i>
                        </div>
                    </div>
                    <div class="pt-0.5">
                        <h3 class="text-sm lg:text-base font-extrabold text-white">Defend</h3>
                        <p class="text-[11px] lg:text-xs text-white/75">Manage research defense schedules</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 flex items-center gap-2 text-xs lg:text-sm text-white/75 font-semibold animate-fade-in-left" style="animation-delay: 200ms;">
            <i class="ph-bold ph-shield-check text-[#E5B72E] text-base"></i>
            <span>Secure Academic Research Portal</span>
        </div>
    </div>

    <!-- Right Side: Strictly NON-SCROLLABLE (overflow-hidden) with Floating Card -->
    <div class="w-full md:w-[54%] lg:w-[56%] flex items-center justify-center p-4 sm:p-6 lg:p-8 relative h-full overflow-hidden bg-[#F7FAF8]">
        <!-- Subtle background graphics -->
        <svg class="absolute inset-0 w-full h-full opacity-30 pointer-events-none" viewBox="0 0 600 800" aria-hidden="true">
            <defs>
                <pattern id="dots-auth-clean" x="0" y="0" width="30" height="30" patternUnits="userSpaceOnUse">
                    <circle cx="1.5" cy="1.5" r="0.8" fill="#087443" opacity="0.1" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots-auth-clean)" />
            <circle cx="500" cy="100" r="220" fill="#087443" opacity="0.03" />
            <circle cx="100" cy="600" r="180" fill="#087443" opacity="0.04" />
        </svg>

        <!-- Close Button -->
        <a href="{{ url('/') }}" class="absolute top-5 right-5 w-10 h-10 rounded-full bg-white border border-[#DDE5E1] flex items-center justify-center text-[#68766F] hover:text-[#00633E] hover:bg-[#F1F7F4] shadow-md transition-all duration-300 z-30 hover:-rotate-90" title="Back to home">
            <i class="ph-bold ph-x text-base"></i>
        </a>

        <!-- Floating Authentication Card -->
        <div class="relative w-full max-w-[480px] sm:max-w-[500px] lg:max-w-[520px] my-auto animate-fade-in-up">
            <!-- Floating Ambient Back Glow -->
            <div class="absolute -inset-3 rounded-[38px] bg-gradient-to-tr from-[#003D29]/30 via-[#E5B72E]/20 to-[#005337]/30 blur-2xl opacity-75 pointer-events-none"></div>

            <!-- Floating Main Card Body -->
            <div class="relative bg-white rounded-3xl shadow-[0_20px_70px_-15px_rgba(0,61,41,0.32),0_10px_25px_-5px_rgba(0,0,0,0.1)] border border-[#CFE3D8]/80 p-6 sm:p-8 lg:p-9 flex flex-col overflow-hidden transition-all duration-500 hover:shadow-[0_30px_85px_-15px_rgba(0,61,41,0.4)]">
                <!-- Card top accent -->
                <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full bg-gradient-to-br from-[#087443]/10 via-[#087443]/5 to-transparent pointer-events-none"></div>

                <!-- Tabs -->
                <div class="flex bg-[#F1F7F4] p-1.5 rounded-2xl mb-6 relative z-10 border border-[#DDE5E1]/70 shadow-inner">
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
                <form method="POST" action="{{ route('login.store') }}" class="space-y-4 mb-4 relative z-10">
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
                                class="w-full pl-11 pr-4 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-xs sm:text-sm font-medium text-[#0f291e] placeholder-[#8B9690] focus:outline-none focus:border-[#00633E] focus:ring-4 focus:ring-[#00633E]/10 transition-all duration-200"
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
                                class="w-full pl-11 pr-11 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-xs sm:text-sm font-medium text-[#0f291e] placeholder-[#8B9690] focus:outline-none focus:border-[#00633E] focus:ring-4 focus:ring-[#00633E]/10 transition-all duration-200"
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
                    <button type="submit" class="w-full py-3.5 bg-[#00633E] hover:bg-[#004F32] text-white text-xs sm:text-sm font-extrabold rounded-2xl flex items-center justify-center gap-2 shadow-md shadow-[#00633E]/20 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 active:translate-y-0 mt-1">
                        <span>Sign In</span>
                        <i class="ph-bold ph-arrow-right text-base"></i>
                    </button>
                </form>

                <!-- Staff Notice -->
                <div class="border border-[#CFE3D8] bg-[#EAF5EF] rounded-2xl p-3.5 relative z-10">
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

    <!-- Floating Help Button -->
    <a href="#" class="absolute bottom-5 right-5 w-10 h-10 rounded-full bg-white border border-[#DDE5E1] flex items-center justify-center text-[#68766F] hover:text-[#00633E] hover:bg-[#F1F7F4] shadow-md transition-all duration-200 hover:scale-105 z-30" title="Help">
        <i class="ph-bold ph-question text-base"></i>
    </a>
</div>
@endsection
