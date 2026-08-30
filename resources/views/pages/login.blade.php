@extends('layouts.auth')

@section('auth-content')
<div class="min-h-screen w-full bg-gradient-to-br from-[#021a10] via-[#063823] to-[#02140c] text-white flex flex-col justify-between p-4 sm:p-6 lg:p-8 relative overflow-x-hidden selection:bg-[#eebc3f] selection:text-[#073823]">
    <!-- Ambient Dynamic Lighting & Glow Orbs -->
    <div class="pointer-events-none absolute -right-20 -top-20 h-[480px] w-[480px] rounded-full bg-gradient-to-br from-[#eebc3f]/20 via-amber-500/10 to-transparent blur-3xl"></div>
    <div class="pointer-events-none absolute -left-20 top-1/4 h-[520px] w-[520px] rounded-full bg-gradient-to-tr from-emerald-500/20 via-[#0e5c3a]/25 to-transparent blur-3xl"></div>
    <div class="pointer-events-none absolute right-1/4 -bottom-24 h-[440px] w-[440px] rounded-full bg-gradient-to-t from-emerald-400/15 via-[#eebc3f]/10 to-transparent blur-3xl"></div>
    <div class="pointer-events-none absolute left-1/3 top-10 h-72 w-72 rounded-full bg-teal-400/10 blur-2xl"></div>

    <!-- Architectural Background Grid & Subtle Isometric Lines -->
    <div class="pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_center,black_40%,transparent_80%)] opacity-20">
        <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
            <defs>
                <pattern id="grid-pattern-modern" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(238,188,63,0.3)" stroke-width="0.8" />
                    <circle cx="40" cy="40" r="1.2" fill="rgba(255,255,255,0.6)" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid-pattern-modern)" />
        </svg>
    </div>

    <!-- Watermark University Crest Silhouette -->
    <div class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 opacity-[0.04] select-none">
        <img src="{{ asset('images/ndmu_logo.png') }}" alt="" class="h-[680px] w-auto object-contain">
    </div>

    <!-- Top Bar: Brand Identity & Quick Navigation -->
    <header class="relative z-20 w-full max-w-7xl mx-auto flex items-center justify-between">
        <!-- University Logo & Brand -->
        <a href="{{ url('/') }}" class="flex items-center gap-3.5 group">
            <div class="relative">
                <img src="{{ asset('images/ndmu_logo.png') }}" alt="NDMU Logo" class="h-11 sm:h-13 w-auto object-contain transition-transform duration-300 group-hover:scale-105 drop-shadow-md">
            </div>
            <div class="flex flex-col leading-none border-l border-white/25 pl-3.5">
                <div class="flex items-center gap-2">
                    <span class="font-heading font-black text-xl sm:text-2xl text-white tracking-wider">NDMU</span>
                    <span class="px-2 py-0.5 rounded-full bg-[#eebc3f]/20 border border-[#eebc3f]/40 text-[#eebc3f] font-black text-[9px] uppercase tracking-wider hidden sm:inline-block">RMAS</span>
                </div>
                <span class="text-[9px] sm:text-[10px] font-black text-[#eebc3f] tracking-[0.25em] uppercase mt-1">Research Management</span>
            </div>
        </a>

        <!-- Back to Home Button -->
        <a
            href="{{ url('/') }}"
            class="group inline-flex items-center gap-2 px-4.5 py-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white text-xs font-bold shadow-lg shadow-emerald-950/20 transition-all duration-300 hover:scale-105 hover:border-[#eebc3f]/50 cursor-pointer"
        >
            <i class="ph ph-arrow-left text-sm text-[#eebc3f] group-hover:-translate-x-1 transition-transform"></i>
            <span>Back to Home</span>
        </a>
    </header>

    <!-- Main Content: Enhanced Centered Authentication Card -->
    <main class="relative z-20 w-full my-auto py-8 flex items-center justify-center">
        <div class="w-full max-w-[460px] animate-fade-in-up">
            <!-- Ambient Card Backglow -->
            <div class="relative">
                <div class="absolute -inset-2 rounded-[32px] bg-gradient-to-tr from-[#0e5c3a] via-[#eebc3f]/30 to-emerald-400/40 blur-2xl opacity-60 pointer-events-none"></div>

                <!-- Main Card Container -->
                <div class="relative bg-white/98 backdrop-blur-xl rounded-[28px] shadow-[0_25px_70px_-15px_rgba(2,20,12,0.55),0_0_0_1px_rgba(14,92,58,0.1)] p-7 sm:p-8 text-slate-800 overflow-hidden">
                    <!-- Top Multi-Tone Brand Accent Stripe with Gold Accent Glow -->
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

                    <!-- Segmented Auth Tab Switcher -->
                    <div class="flex bg-slate-100/90 p-1.5 rounded-2xl mb-6 border border-slate-200/90 shadow-inner">
                        <a
                            href="{{ route('login') }}"
                            class="flex-1 flex items-center justify-center gap-2 py-2.5 text-xs sm:text-sm font-black rounded-xl bg-gradient-to-r from-[#073823] via-[#0e5c3a] to-[#0a462c] text-white shadow-md shadow-emerald-950/25 border border-emerald-600/30 transition-all"
                        >
                            <i class="ph ph-sign-in text-base text-[#eebc3f]"></i>
                            <span>Sign In</span>
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="flex-1 flex items-center justify-center gap-2 py-2.5 text-xs sm:text-sm font-bold rounded-xl text-slate-600 hover:text-[#0e5c3a] hover:bg-white/70 transition-all"
                        >
                            <i class="ph ph-user-plus text-base"></i>
                            <span>Register</span>
                        </a>
                    </div>

                    <!-- Header: Simple N Logo & Greeting -->
                    <div class="text-center mb-6 flex flex-col items-center">
                        <x-ndmu-n-logo size="lg" class="mb-3" />

                        <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-[10px] font-black uppercase tracking-[0.2em] text-[#0e5c3a] mb-1.5 shadow-2xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#0e5c3a] animate-pulse"></span>
                            <span>Academic Portal</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black font-heading text-slate-900 tracking-tight">
                            Welcome Back
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1 max-w-xs">
                            Enter your credentials to access your research workspace
                        </p>
                    </div>

                    <!-- Flash Message Status -->
                    @if (session('status'))
                        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-3.5 text-xs sm:text-sm font-semibold text-emerald-800 flex items-center gap-2.5 animate-fade-in" role="status">
                            <i class="ph ph-check-circle text-base text-emerald-600 shrink-0"></i>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    <!-- Validation Errors -->
                    @if ($errors->any())
                        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50/90 p-3.5 text-xs sm:text-sm font-semibold text-rose-700 flex items-center gap-2.5 animate-fade-in" role="alert">
                            <i class="ph ph-warning-circle text-base text-rose-600 shrink-0"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <!-- Sign In Form -->
                    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                        @csrf

                        <!-- Email Input -->
                        <div class="space-y-1.5">
                            <label for="email" class="text-[11px] font-bold text-slate-700 block uppercase tracking-wider">
                                Email Address
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#0e5c3a] pointer-events-none">
                                    <i class="ph ph-envelope-simple text-base sm:text-lg"></i>
                                </span>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    autocomplete="username"
                                    required
                                    placeholder="your.email@ndmu.edu.ph"
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50/80 hover:bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/12 transition-all duration-200 shadow-2xs"
                                >
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label for="password" class="text-[11px] font-bold text-slate-700 block uppercase tracking-wider">
                                    Password
                                </label>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#0e5c3a] pointer-events-none">
                                    <i class="ph ph-lock text-base sm:text-lg"></i>
                                </span>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    placeholder="Enter your password"
                                    class="w-full pl-10 pr-11 py-3 bg-slate-50/80 hover:bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/12 transition-all duration-200 shadow-2xs"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-[#0e5c3a] transition-colors duration-200 cursor-pointer"
                                    data-password-toggle
                                    data-password-input="password"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    <i data-password-show-icon class="ph ph-eye text-base sm:text-lg" aria-hidden="true"></i>
                                    <i data-password-hide-icon class="ph ph-eye-slash text-base sm:text-lg hidden" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="flex items-center justify-between gap-4 text-xs sm:text-sm pt-0.5">
                            <label class="flex items-center gap-2 text-slate-600 cursor-pointer hover:text-slate-900 transition-colors font-medium select-none">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                    @checked(old('remember'))
                                    class="w-4 h-4 rounded border-slate-300 text-[#0e5c3a] focus:ring-[#0e5c3a]"
                                >
                                <span>Remember me</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="font-bold text-[#0e5c3a] hover:text-[#073823] hover:underline transition-colors">
                                Forgot password?
                            </a>
                        </div>

                        <!-- Cloudflare Turnstile Verification -->
                        <x-turnstile />

                        <!-- Submit CTA Button -->
                        <button
                            type="submit"
                            class="w-full py-3.5 bg-gradient-to-r from-[#073823] via-[#0e5c3a] to-[#0a462c] hover:brightness-110 border-t border-[#eebc3f]/30 text-white text-sm font-black rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/30 hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 cursor-pointer mt-2 group"
                        >
                            <span>Sign In to Workspace</span>
                            <i class="ph ph-arrow-right text-base font-bold text-[#eebc3f] group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </form>

                    <!-- Staff & Faculty Notice -->
                    <div class="mt-6 border border-emerald-200/70 bg-emerald-50/70 rounded-2xl p-3.5 flex items-start gap-3 text-xs">
                        <div class="w-7 h-7 rounded-xl bg-emerald-100 text-[#0e5c3a] flex items-center justify-center shrink-0 text-sm mt-0.5 shadow-2xs">
                            <i class="ph ph-info"></i>
                        </div>
                        <div class="text-slate-700 leading-snug space-y-0.5">
                            <p class="font-bold text-slate-900">Faculty &amp; Staff Access</p>
                            <p class="text-slate-500 text-[11px]">
                                Faculty, panelist, and adviser accounts are provisioned by the Research Office. 
                                <a href="mailto:research@ndmu.edu.ph" class="font-bold text-[#0e5c3a] hover:underline">Contact Office</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Footer: Security & Help -->
    <footer class="relative z-20 w-full max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-white/70 font-medium">
        <div class="flex items-center gap-2">
            <i class="ph ph-shield-check text-[#eebc3f] text-sm"></i>
            <span>Secure Academic Research Portal &bull; NDMU-RMAS &copy; {{ now()->year }}</span>
        </div>

        <a
            href="mailto:research@ndmu.edu.ph"
            class="inline-flex items-center gap-2 text-white/80 hover:text-[#eebc3f] transition-colors"
        >
            <i class="ph ph-question text-sm text-[#eebc3f]"></i>
            <span>Need assistance? Contact Research Support</span>
        </a>
    </footer>
</div>
@endsection
