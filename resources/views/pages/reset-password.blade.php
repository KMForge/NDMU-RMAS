@extends('layouts.auth')

@section('auth-content')
<div class="min-h-screen w-full bg-gradient-to-br from-[#021a10] via-[#063823] to-[#02140c] text-white flex flex-col justify-between p-4 sm:p-6 lg:p-8 relative overflow-x-hidden selection:bg-[#eebc3f] selection:text-[#073823]">
    <!-- Ambient Dynamic Lighting & Glow Orbs -->
    <div class="pointer-events-none absolute -right-20 -top-20 h-[480px] w-[480px] rounded-full bg-gradient-to-br from-[#eebc3f]/20 via-amber-500/10 to-transparent blur-3xl"></div>
    <div class="pointer-events-none absolute -left-20 top-1/4 h-[520px] w-[520px] rounded-full bg-gradient-to-tr from-emerald-500/20 via-[#0e5c3a]/25 to-transparent blur-3xl"></div>

    <!-- Architectural Background Grid -->
    <div class="pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_center,black_40%,transparent_80%)] opacity-20">
        <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
            <defs>
                <pattern id="grid-pattern-modern-reset" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(238,188,63,0.3)" stroke-width="0.8" />
                    <circle cx="40" cy="40" r="1.2" fill="rgba(255,255,255,0.6)" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid-pattern-modern-reset)" />
        </svg>
    </div>

    <!-- Watermark University Crest -->
    <div class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 opacity-[0.04] select-none">
        <img src="{{ asset('images/ndmu_logo.png') }}" alt="" class="h-[680px] w-auto object-contain">
    </div>

    <!-- Top Bar -->
    <header class="relative z-20 w-full max-w-7xl mx-auto flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-3.5 group">
            <img src="{{ asset('images/ndmu_logo.png') }}" alt="NDMU Logo" class="h-11 sm:h-13 w-auto object-contain transition-transform group-hover:scale-105">
            <div class="flex flex-col leading-none border-l border-white/25 pl-3.5">
                <span class="font-heading font-black text-xl sm:text-2xl text-white tracking-wider">NDMU</span>
                <span class="text-[9px] sm:text-[10px] font-black text-[#eebc3f] tracking-[0.25em] uppercase mt-1">Research Management</span>
            </div>
        </a>

        <a
            href="{{ route('login') }}"
            class="group inline-flex items-center gap-2 px-4.5 py-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white text-xs font-bold shadow-lg shadow-emerald-950/20 transition-all duration-300 hover:scale-105 hover:border-[#eebc3f]/50 cursor-pointer"
        >
            <i class="ph ph-arrow-left text-sm text-[#eebc3f] group-hover:-translate-x-1 transition-transform"></i>
            <span>Back to Sign In</span>
        </a>
    </header>

    <!-- Main Content: Centered Card -->
    <main class="relative z-20 w-full my-auto py-8 flex items-center justify-center">
        <div class="w-full max-w-[460px] animate-fade-in-up">
            <div class="relative">
                <div class="absolute -inset-2 rounded-[32px] bg-gradient-to-tr from-[#0e5c3a] via-[#eebc3f]/30 to-emerald-400/40 blur-2xl opacity-60 pointer-events-none"></div>

                <div class="relative bg-white/98 backdrop-blur-xl rounded-[28px] shadow-[0_25px_70px_-15px_rgba(2,20,12,0.55),0_0_0_1px_rgba(14,92,58,0.1)] p-7 sm:p-8 text-slate-800 overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

                    <!-- Header with N Logo -->
                    <div class="text-center mb-6 flex flex-col items-center">
                        <x-ndmu-n-logo size="lg" class="mb-3" />
                        <h1 class="text-2xl sm:text-3xl font-black font-heading text-slate-900 tracking-tight">
                            Reset Password
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">
                            Choose a secure new password for your account.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50/90 p-3.5 text-xs sm:text-sm font-semibold text-rose-700 flex items-center gap-2.5 animate-fade-in" role="alert">
                            <i class="ph ph-warning-circle text-base text-rose-600 shrink-0"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <!-- Email -->
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
                                    value="{{ old('email', $email) }}"
                                    autocomplete="email"
                                    required
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50/80 hover:bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/12 transition-all shadow-2xs"
                                >
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="space-y-1.5">
                            <label for="password" class="text-[11px] font-bold text-slate-700 block uppercase tracking-wider">
                                New Password
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#0e5c3a] pointer-events-none">
                                    <i class="ph ph-lock text-base sm:text-lg"></i>
                                </span>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                    autofocus
                                    placeholder="Min. 12 characters"
                                    class="w-full pl-10 pr-11 py-3 bg-slate-50/80 hover:bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/12 transition-all shadow-2xs"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-[#0e5c3a] transition-colors cursor-pointer"
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

                        <!-- Confirm Password -->
                        <div class="space-y-1.5">
                            <label for="password_confirmation" class="text-[11px] font-bold text-slate-700 block uppercase tracking-wider">
                                Confirm New Password
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-[#0e5c3a] pointer-events-none">
                                    <i class="ph ph-shield-check text-base sm:text-lg"></i>
                                </span>
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autocomplete="new-password"
                                    required
                                    placeholder="Re-enter password"
                                    class="w-full pl-10 pr-11 py-3 bg-slate-50/80 hover:bg-white border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-emerald-600/12 transition-all shadow-2xs"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-[#0e5c3a] transition-colors cursor-pointer"
                                    data-password-toggle
                                    data-password-input="password_confirmation"
                                    aria-label="Show password confirmation"
                                    aria-pressed="false"
                                >
                                    <i data-password-show-icon class="ph ph-eye text-base sm:text-lg" aria-hidden="true"></i>
                                    <i data-password-hide-icon class="ph ph-eye-slash text-base sm:text-lg hidden" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="w-full py-3.5 bg-gradient-to-r from-[#073823] via-[#0e5c3a] to-[#0a462c] hover:brightness-110 border-t border-[#eebc3f]/30 text-white text-sm font-black rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/30 hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 cursor-pointer mt-2 group"
                        >
                            <i class="ph ph-check-circle text-base font-bold text-[#eebc3f]"></i>
                            <span>Reset Password</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Bottom Footer -->
    <footer class="relative z-20 w-full max-w-7xl mx-auto flex items-center justify-center text-xs text-white/70 font-medium">
        <span>NDMU-RMAS &copy; {{ now()->year }} &bull; Research Management &amp; Archiving System</span>
    </footer>
</div>
@endsection
