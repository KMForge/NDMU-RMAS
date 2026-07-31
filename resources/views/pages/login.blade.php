@extends('layouts.auth')

@section('auth-content')
<div class="min-h-screen flex flex-col md:flex-row relative bg-[#f4f7f6]">
    <!-- Left Side: Image Banner & Brand Description -->
    <div class="w-full md:w-[45%] lg:w-[40%] bg-[#0e5c3a] text-white p-8 md:p-16 flex flex-col justify-between relative min-h-[400px] md:min-h-screen overflow-hidden">
        <img src="{{ asset('images/ndmu.jpg') }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover">
        <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-b from-[rgba(14,92,58,0.82)] to-[rgba(10,70,44,0.88)]"></div>

        <!-- Logo -->
        <div class="relative z-10 flex items-center gap-3">
            <img src="{{ asset('images/ndmu_logo.png') }}" alt="NDMU Logo" class="h-12 w-auto">
            <div class="flex flex-col leading-none">
                <span class="font-heading font-extrabold text-2xl text-white tracking-tight">NDMU</span>
                <span class="text-[10px] font-bold text-[#eebc3f] tracking-wider uppercase mt-1">Research Management</span>
            </div>
        </div>

        <!-- Banner Text Content -->
        <div class="relative z-10 my-auto py-12 space-y-6">
            <span class="text-xs font-bold tracking-widest text-[#eebc3f] uppercase block">Welcome Back</span>
            <h1 class="text-4xl md:text-5xl font-heading font-bold text-white leading-tight">
                Access Your<br>Research Portal
            </h1>
            <p class="text-white/80 text-sm md:text-base font-light max-w-sm leading-relaxed">
                Sign in to manage your research projects, schedule defenses, and collaborate with advisers.
            </p>
            
            <!-- Features list -->
            <ul class="space-y-4 pt-4 text-sm font-medium">
                <li class="flex items-center gap-3 text-white/95">
                    <span class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center border border-white/20 text-[#eebc3f] flex-shrink-0">
                        <i class="ph ph-check text-xs"></i>
                    </span>
                    <span>Access your assigned research dashboard</span>
                </li>
                <li class="flex items-center gap-3 text-white/95">
                    <span class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center border border-white/20 text-[#eebc3f] flex-shrink-0">
                        <i class="ph ph-check text-xs"></i>
                    </span>
                    <span>Submit and track research proposals</span>
                </li>
                <li class="flex items-center gap-3 text-white/95">
                    <span class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center border border-white/20 text-[#eebc3f] flex-shrink-0">
                        <i class="ph ph-check text-xs"></i>
                    </span>
                    <span>Collaborate with advisers and panelists</span>
                </li>
                <li class="flex items-center gap-3 text-white/95">
                    <span class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center border border-white/20 text-[#eebc3f] flex-shrink-0">
                        <i class="ph ph-check text-xs"></i>
                    </span>
                    <span>Schedule and manage defense sessions</span>
                </li>
            </ul>
        </div>

        <!-- Bottom spacer/branding link -->
        <div class="relative z-10 text-xs text-white/50 font-medium">
            © 2026 Notre Dame of Marbel University.
        </div>
    </div>

    <!-- Right Side: Login Form Card -->
    <div class="w-full md:w-[55%] lg:w-[60%] flex items-center justify-center p-6 md:p-12 relative min-h-screen bg-gradient-to-tr from-[#f0f4f2] to-[#f4f7f6]">
        <!-- Close Button -->
        <a href="{{ url('/') }}" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-white border border-gray-150 flex items-center justify-center text-gray-500 hover:text-gray-800 shadow-sm transition-all duration-300 z-10 hover:scale-105">
            <i class="ph ph-x text-lg"></i>
        </a>

        <!-- Login Card -->
        <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-gray-100/50 p-8 md:p-10 w-full max-w-[460px] flex flex-col relative">
            <!-- Tabs -->
            <div class="flex bg-gray-100/60 p-1.5 rounded-2xl mb-8">
                <a href="{{ route('login') }}" class="flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold rounded-xl bg-[#0e5c3a] text-white shadow-sm transition-all duration-300">
                    <i class="ph ph-sign-in text-base"></i> Sign In
                </a>
                <a href="{{ route('register') }}" class="flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold rounded-xl text-gray-500 hover:text-gray-800 transition-all duration-300">
                    <i class="ph ph-user-plus text-base"></i> Register
                </a>
            </div>

            <!-- Header Icon & Text -->
            <div class="text-center mb-8 flex flex-col items-center">
                <div class="w-12 h-12 rounded-2xl bg-[#0e5c3a]/10 text-[#0e5c3a] flex items-center justify-center mb-4 text-xl">
                    <i class="ph ph-sign-in"></i>
                </div>
                <h2 class="text-2xl font-bold font-heading text-gray-800 mb-1">Welcome Back</h2>
                <p class="text-xs text-gray-400 font-light">Sign in to access your research portal</p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5 mb-6">
                @csrf

                @if ($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-3 text-xs text-red-700" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- Email Field -->
                <div class="space-y-2">
                    <label for="email" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
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
                            class="w-full pl-11 pr-4 py-3.5 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <label for="password" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                            <i class="ph ph-lock text-lg"></i>
                        </span>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="w-full pl-11 pr-11 py-3.5 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                        >
                        <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="ph ph-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-xs text-gray-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-gray-300 text-[#0e5c3a] focus:ring-[#0e5c3a]">
                    Remember me
                </label>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-4 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-sm font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-[#0e5c3a]/10 hover:shadow-xl transition-all duration-300">
                    <i class="ph ph-sign-in text-base"></i> Sign In
                </button>
            </form>

            <!-- Info Box -->
            <div class="border border-[#0e5c3a]/15 bg-[#0e5c3a]/5 rounded-2xl p-4 text-center">
                <p class="text-[11px] text-[#0e5c3a] leading-relaxed">
                    Are you faculty, an adviser, or a panelist? <span class="font-bold">Staff accounts are created by the administrator.</span> Please contact <a href="mailto:research@ndmu.edu.ph" class="underline font-semibold hover:text-[#0a4a2e]">research@ndmu.edu.ph</a>.
                </p>
            </div>
        </div>
    </div>

    <!-- Floating Help Button -->
    <a href="#" class="absolute bottom-6 right-6 w-10 h-10 rounded-full bg-white border border-gray-150 flex items-center justify-center text-gray-500 hover:text-gray-800 shadow-sm transition-all duration-300 hover:scale-105">
        <i class="ph ph-question text-lg"></i>
    </a>
</div>
@endsection
