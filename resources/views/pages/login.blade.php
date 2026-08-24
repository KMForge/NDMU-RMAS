@extends('layouts.auth')

@section('auth-content')
<div class="min-h-screen flex flex-col md:flex-row relative bg-[#F7FAF8]">
    <!-- Left Side: Premium NDMU Research Brand Panel -->
    <div class="w-full md:w-[40%] bg-gradient-to-br from-[#003D29] via-[#005337] to-[#00462F] text-white p-8 md:p-16 flex flex-col justify-between relative min-h-[500px] md:min-h-screen overflow-hidden">
        <!-- Subtle academic patterns (very faint) -->
        <svg class="absolute inset-0 w-full h-full opacity-3" aria-hidden="true">
            <pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                <circle cx="2" cy="2" r="1" fill="white" />
            </pattern>
            <rect width="100%" height="100%" fill="url(#dots)" />
        </svg>

        <!-- Decorative circles (very subtle) -->
        <div class="absolute -top-40 -right-32 w-96 h-96 rounded-full border border-white/5 pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-40 w-80 h-80 rounded-full border border-white/8 pointer-events-none"></div>

        <!-- Logo -->
        <div class="relative z-10 flex items-center gap-3 animate-fade-in-left">
            <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="NDMU Logo" width="96" height="96" class="h-12 w-auto">
            <div class="flex flex-col leading-none">
                <span class="font-heading font-extrabold text-2xl text-white tracking-tight">NDMU</span>
                <span class="text-[10px] font-bold text-[#E5B72E] tracking-widest uppercase mt-1">Research Management</span>
            </div>
        </div>

        <!-- Main Message Content -->
        <div class="relative z-10 my-auto py-12 space-y-8 animate-fade-in-left" style="animation-delay: 100ms;">
            <div class="space-y-1">
                <span class="text-xs font-bold tracking-widest text-[#E5B72E] uppercase block">Welcome Back</span>
            </div>

            <!-- Hero Headline -->
            <div class="space-y-2">
                <h1 class="text-5xl lg:text-6xl font-serif font-bold text-white leading-tight tracking-tight">
                    Research.<br>
                    <span>Collaborate.</span><br>
                    <span class="text-[#E5B72E]">Achieve More.</span>
                </h1>
            </div>

            <!-- Supporting Text -->
            <p class="text-white/75 text-sm leading-relaxed max-w-sm font-light">
                A centralized research workspace connecting students, faculty, advisers, and panelists throughout the academic research journey.
            </p>

            <!-- Research Journey Timeline -->
            <div class="space-y-6 pt-4">
                <!-- Stage 1: Submit -->
                <div class="flex gap-4 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-white/10 border border-white/25 flex items-center justify-center flex-shrink-0 mb-3 transition-all duration-300 hover:bg-white/15 hover:scale-110">
                            <i class="ph ph-document-plus text-[#E5B72E] text-lg"></i>
                        </div>
                        <div class="w-0.5 h-12 bg-gradient-to-b from-[#E5B72E]/30 to-transparent"></div>
                    </div>
                    <div class="pt-1">
                        <h3 class="text-sm font-bold text-white">Submit</h3>
                        <p class="text-xs text-white/60 mt-0.5">Upload and manage research proposals</p>
                    </div>
                </div>

                <!-- Stage 2: Collaborate -->
                <div class="flex gap-4 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-white/10 border border-white/25 flex items-center justify-center flex-shrink-0 mb-3 transition-all duration-300 hover:bg-white/15 hover:scale-110">
                            <i class="ph ph-users-three text-[#E5B72E] text-lg"></i>
                        </div>
                        <div class="w-0.5 h-12 bg-gradient-to-b from-[#E5B72E]/30 to-transparent"></div>
                    </div>
                    <div class="pt-1">
                        <h3 class="text-sm font-bold text-white">Collaborate</h3>
                        <p class="text-xs text-white/60 mt-0.5">Work with advisers and panelists</p>
                    </div>
                </div>

                <!-- Stage 3: Review -->
                <div class="flex gap-4 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-white/10 border border-white/25 flex items-center justify-center flex-shrink-0 mb-3 transition-all duration-300 hover:bg-white/15 hover:scale-110">
                            <i class="ph ph-file-text text-[#E5B72E] text-lg"></i>
                        </div>
                        <div class="w-0.5 h-12 bg-gradient-to-b from-[#E5B72E]/30 to-transparent"></div>
                    </div>
                    <div class="pt-1">
                        <h3 class="text-sm font-bold text-white">Review</h3>
                        <p class="text-xs text-white/60 mt-0.5">Track feedback and research progress</p>
                    </div>
                </div>

                <!-- Stage 4: Defend -->
                <div class="flex gap-4 items-start">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-full bg-white/10 border border-white/25 flex items-center justify-center flex-shrink-0 transition-all duration-300 hover:bg-white/15 hover:scale-110">
                            <i class="ph ph-calendar text-[#E5B72E] text-lg"></i>
                        </div>
                    </div>
                    <div class="pt-1">
                        <h3 class="text-sm font-bold text-white">Defend</h3>
                        <p class="text-xs text-white/60 mt-0.5">Manage research defense schedules</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Research Network Visualization (subtle, background) -->
        <svg class="absolute bottom-0 right-0 w-80 h-80 opacity-5 pointer-events-none" viewBox="0 0 300 300" aria-hidden="true">
            <!-- Research network nodes and connections -->
            <line x1="150" y1="150" x2="80" y2="100" stroke="#46BE7D" stroke-width="0.5" />
            <line x1="150" y1="150" x2="220" y2="100" stroke="#46BE7D" stroke-width="0.5" />
            <line x1="150" y1="150" x2="120" y2="240" stroke="#46BE7D" stroke-width="0.5" />
            <line x1="150" y1="150" x2="220" y2="220" stroke="#46BE7D" stroke-width="0.5" />
            <line x1="80" y1="100" x2="220" y2="100" stroke="#46BE7D" stroke-width="0.5" />

            <circle cx="150" cy="150" r="8" fill="none" stroke="#E5B72E" stroke-width="1" opacity="0.3" class="animate-pulse-subtle" />
            <circle cx="80" cy="100" r="6" fill="none" stroke="#46BE7D" stroke-width="0.5" />
            <circle cx="220" cy="100" r="6" fill="none" stroke="#46BE7D" stroke-width="0.5" />
            <circle cx="120" cy="240" r="6" fill="none" stroke="#46BE7D" stroke-width="0.5" />
            <circle cx="220" cy="220" r="6" fill="none" stroke="#46BE7D" stroke-width="0.5" />
        </svg>

        <!-- Footer -->
        <div class="relative z-10 flex items-center gap-2 text-xs text-white/60 font-medium animate-fade-in-left" style="animation-delay: 200ms;">
            <i class="ph ph-shield-check text-[#E5B72E]"></i>
            <span>Secure Academic Research Portal</span>
        </div>
    </div>

    <!-- Right Side: Premium Authentication Area -->
    <div class="w-full md:w-[60%] flex items-center justify-center p-6 md:p-12 relative min-h-screen bg-[#F7FAF8]">
        <!-- Subtle background graphics -->
        <svg class="absolute inset-0 w-full h-full opacity-40 pointer-events-none" viewBox="0 0 600 800" aria-hidden="true">
            <defs>
                <pattern id="dots-auth" x="0" y="0" width="30" height="30" patternUnits="userSpaceOnUse">
                    <circle cx="1" cy="1" r="0.5" fill="#087443" opacity="0.08" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots-auth)" />
            <circle cx="500" cy="100" r="200" fill="#087443" opacity="0.02" />
            <circle cx="100" cy="600" r="150" fill="#087443" opacity="0.03" />
        </svg>

        <!-- Close Button -->
        <a href="{{ url('/') }}" class="absolute top-6 right-6 w-11 h-11 rounded-full bg-white border border-[#DDE5E1] flex items-center justify-center text-[#68766F] hover:text-[#14231D] hover:bg-[#F1F7F4] shadow-sm transition-all duration-300 z-10 hover:-rotate-90" title="Close">
            <i class="ph ph-x text-lg"></i>
        </a>

        <!-- Authentication Card -->
        <div class="bg-white rounded-3xl shadow-lg shadow-[#087443]/8 border border-[#CFE3D8] p-8 md:p-10 w-full max-w-[500px] flex flex-col relative overflow-hidden animate-fade-in-up">
            <!-- Card accent (top-left subtle geometric) -->
            <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full bg-gradient-to-br from-[#087443]/5 to-transparent pointer-events-none"></div>

            <!-- Tabs -->
            <div class="flex bg-[#F1F7F4] p-1 rounded-2xl mb-8 relative z-10">
                <a href="{{ route('login') }}" class="flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold rounded-xl bg-[#00633E] text-white shadow-sm transition-all duration-300">
                    <i class="ph ph-sign-in text-base"></i> Sign In
                </a>
                <a href="{{ route('register') }}" class="flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold rounded-xl text-[#68766F] hover:text-[#14231D] transition-all duration-300">
                    <i class="ph ph-user-plus text-base"></i> Register
                </a>
            </div>

            <!-- Header -->
            <div class="text-center mb-8 flex flex-col items-center relative z-10">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#087443]/10 to-[#087443]/5 text-[#087443] flex items-center justify-center mb-4 text-2xl border border-[#CFE3D8]/50 transition-all duration-500 hover:scale-110">
                    <i class="ph ph-shield-check"></i>
                </div>
                <h2 class="text-2xl font-bold text-[#14231D] mb-2">Welcome Back</h2>
                <p class="text-sm text-[#68766F] font-light">Continue to your research workspace.</p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5 mb-6 relative z-10">
                @csrf

                @if (session('status'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 animate-slide-down" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 animate-slide-down" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- Email Field -->
                <div class="space-y-2">
                    <label for="email" class="text-sm font-semibold text-[#14231D] block">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#8B9690] pointer-events-none">
                            <i class="ph ph-envelope-simple text-lg"></i>
                        </span>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="username"
                            required
                            placeholder="your.email@ndmu.edu.ph"
                            class="w-full pl-12 pr-4 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-sm text-[#14231D] placeholder-[#8B9690] focus:outline-none focus:border-[#087443] focus:ring-4 focus:ring-[#087443]/10 transition-all duration-300"
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="password" class="text-sm font-semibold text-[#14231D] block">Password</label>
                    </div>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#8B9690] pointer-events-none">
                            <i class="ph ph-lock text-lg"></i>
                        </span>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="Enter your password"
                            class="w-full pl-12 pr-12 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-sm text-[#14231D] placeholder-[#8B9690] focus:outline-none focus:border-[#087443] focus:ring-4 focus:ring-[#087443]/10 transition-all duration-300"
                        >
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#8B9690] hover:text-[#087443] transition-colors duration-300"
                            data-password-toggle
                            data-password-input="password"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <i data-password-show-icon class="ph ph-eye text-lg" aria-hidden="true"></i>
                            <i data-password-hide-icon class="ph ph-eye-slash text-lg hidden" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between gap-4 text-sm">
                    <label class="flex items-center gap-2 text-[#68766F] cursor-pointer hover:text-[#14231D] transition-colors">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-[#DDE5E1] text-[#087443] focus:ring-[#087443]">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="font-semibold text-[#087443] hover:text-[#00633E] hover:underline transition-colors">
                        Forgot password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-[#00633E] hover:bg-[#004F32] text-white text-sm font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-[#087443]/15 hover:shadow-xl hover:shadow-[#087443]/20 hover:-translate-y-0.5 transition-all duration-300 active:translate-y-0">
                    Sign In <i class="ph ph-arrow-right text-base transition-transform duration-300 group-hover:translate-x-1"></i>
                </button>
            </form>

            <!-- Staff Account Notice -->
            <div class="border border-[#CFE3D8] bg-[#EAF5EF] rounded-2xl p-4 relative z-10">
                <div class="flex gap-3">
                    <i class="ph ph-shield-check text-[#087443] flex-shrink-0 text-lg mt-0.5"></i>
                    <div class="text-xs text-[#14231D] space-y-1">
                        <p class="font-semibold">Staff Account Access</p>
                        <p class="text-[#68766F]">Faculty and staff accounts are managed by the Research Office. <a href="mailto:research@ndmu.edu.ph" class="font-semibold text-[#087443] hover:text-[#00633E] underline">Contact Research Office</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Help Button -->
    <a href="#" class="absolute bottom-8 right-8 w-11 h-11 rounded-full bg-white border border-[#DDE5E1] flex items-center justify-center text-[#68766F] hover:text-[#087443] hover:bg-[#F1F7F4] shadow-sm transition-all duration-300 hover:scale-110" title="Help">
        <i class="ph ph-question text-lg"></i>
    </a>
</div>
@endsection
