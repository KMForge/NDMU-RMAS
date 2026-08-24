@extends('layouts.auth')

@section('auth-content')
<div x-data="{ showPassword: false, showConfirmation: false }" class="min-h-screen flex flex-col md:flex-row relative bg-[#F7FAF8]">
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
                <span class="text-xs font-bold tracking-widest text-[#E5B72E] uppercase block">Student Registration</span>
            </div>

            <!-- Hero Headline -->
            <div class="space-y-2">
                <h1 class="text-5xl lg:text-6xl font-serif font-bold text-white leading-tight tracking-tight">
                    Begin Your<br>
                    <span class="text-[#E5B72E]">Research Journey.</span>
                </h1>
            </div>

            <!-- Supporting Text -->
            <p class="text-white/75 text-sm leading-relaxed max-w-sm font-light">
                Create your student research account and become part of NDMU's collaborative academic research community.
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

            <!-- Account Approval Notice -->
            <div class="border border-white/15 bg-white/5 rounded-2xl p-3.5 mt-6">
                <div class="flex gap-2">
                    <i class="ph ph-info text-[#E5B72E] flex-shrink-0 text-lg"></i>
                    <p class="text-xs text-white/75 leading-relaxed">
                        <span class="font-semibold text-white">Account Approval</span><br>
                        Student registrations are reviewed by the administrator. You'll receive an email once your account has been activated.
                    </p>
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

    <!-- Right Side: Premium Registration Area -->
    <div class="w-full md:w-[60%] flex items-center justify-center p-6 md:p-12 relative min-h-screen bg-[#F7FAF8] overflow-y-auto">
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

        <!-- Registration Card -->
        <div class="bg-white rounded-3xl shadow-lg shadow-[#087443]/8 border border-[#CFE3D8] p-8 md:p-10 w-full max-w-[560px] flex flex-col relative overflow-hidden my-8 animate-fade-in-up">
            <!-- Card accent (top-left subtle geometric) -->
            <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full bg-gradient-to-br from-[#087443]/5 to-transparent pointer-events-none"></div>

            <!-- Tabs -->
            <div class="flex bg-[#F1F7F4] p-1 rounded-2xl mb-8 relative z-10">
                <a href="{{ route('login') }}" class="flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold rounded-xl text-[#68766F] hover:text-[#14231D] transition-all duration-300">
                    <i class="ph ph-sign-in text-base"></i> Sign In
                </a>
                <a href="{{ route('register') }}" class="flex-1 flex items-center justify-center gap-2 py-3 text-xs font-bold rounded-xl bg-[#00633E] text-white shadow-sm transition-all duration-300">
                    <i class="ph ph-user-plus text-base"></i> Register
                </a>
            </div>

            <!-- Header -->
            <div class="text-center mb-8 flex flex-col items-center relative z-10">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#087443]/10 to-[#087443]/5 text-[#087443] flex items-center justify-center mb-4 text-2xl border border-[#CFE3D8]/50 transition-all duration-500 hover:scale-110">
                    <i class="ph ph-user-plus"></i>
                </div>
                <h2 class="text-2xl font-bold text-[#14231D] mb-2">Create Your Account</h2>
                <p class="text-sm text-[#68766F] font-light">Start your NDMU research journey.</p>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('register.store') }}" class="space-y-4 mb-6 relative z-10">
                @csrf

                @if ($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 animate-slide-down" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <!-- Row 1: Student ID & Full Name -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Student ID Field -->
                    <div class="space-y-2">
                        <label for="student_id" class="text-sm font-semibold text-[#14231D] block">Student ID</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#8B9690] pointer-events-none">
                                <i class="ph ph-identification-card text-lg"></i>
                            </span>
                            <input
                                id="student_id"
                                name="student_id"
                                type="text"
                                value="{{ old('student_id') }}"
                                autocomplete="off"
                                required
                                placeholder="STU-2026-0001"
                                class="w-full pl-12 pr-3 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-sm text-[#14231D] placeholder-[#8B9690] focus:outline-none focus:border-[#087443] focus:ring-4 focus:ring-[#087443]/10 transition-all duration-300"
                            >
                        </div>
                    </div>

                    <!-- Full Name Field -->
                    <div class="space-y-2">
                        <label for="full_name" class="text-sm font-semibold text-[#14231D] block">Full Name</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#8B9690] pointer-events-none">
                                <i class="ph ph-user text-lg"></i>
                            </span>
                            <input
                                id="full_name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                autocomplete="name"
                                required
                                placeholder="Juan Dela Cruz"
                                class="w-full pl-12 pr-3 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-sm text-[#14231D] placeholder-[#8B9690] focus:outline-none focus:border-[#087443] focus:ring-4 focus:ring-[#087443]/10 transition-all duration-300"
                            >
                        </div>
                    </div>
                </div>

                <!-- Institutional Email Field -->
                <div class="space-y-2">
                    <label for="email" class="text-sm font-semibold text-[#14231D] block">Institutional Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#8B9690] pointer-events-none">
                            <i class="ph ph-envelope-simple text-lg"></i>
                        </span>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            placeholder="juan.delacruz@ndmu.edu.ph"
                            class="w-full pl-12 pr-3 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-sm text-[#14231D] placeholder-[#8B9690] focus:outline-none focus:border-[#087443] focus:ring-4 focus:ring-[#087443]/10 transition-all duration-300"
                        >
                    </div>
                </div>

                <!-- Row 2: Program & Year Level -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Program / Degree Field -->
                    <div class="space-y-2">
                        <label for="program" class="text-sm font-semibold text-[#14231D] block">Program / Degree</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#8B9690] pointer-events-none">
                                <i class="ph ph-book-open text-lg"></i>
                            </span>
                            <select
                                id="program"
                                name="program"
                                class="w-full pl-12 pr-10 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-sm text-[#14231D] focus:outline-none focus:border-[#087443] focus:ring-4 focus:ring-[#087443]/10 transition-all duration-300 appearance-none"
                            >
                                <option value="" disabled @selected(old('program') === null)>Select program</option>
                                @foreach (config('academic.programs') as $program)
                                    <option value="{{ $program['label'] }}" @selected(old('program') === $program['label'])>{{ $program['label'] }}</option>
                                @endforeach
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#8B9690] pointer-events-none">
                                <i class="ph ph-caret-down text-base"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Year Level Field -->
                    <div class="space-y-2">
                        <label for="year_level" class="text-sm font-semibold text-[#14231D] block">Year Level</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#8B9690] pointer-events-none">
                                <i class="ph ph-graduation-cap text-lg"></i>
                            </span>
                            <select
                                id="year_level"
                                name="year_level"
                                required
                                class="w-full pl-12 pr-10 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-sm text-[#14231D] focus:outline-none focus:border-[#087443] focus:ring-4 focus:ring-[#087443]/10 transition-all duration-300 appearance-none"
                            >
                                <option value="" disabled @selected(old('year_level') === null)>Select level</option>
                                <option value="1" @selected(old('year_level') == 1)>1st Year</option>
                                <option value="2" @selected(old('year_level') == 2)>2nd Year</option>
                                <option value="3" @selected(old('year_level') == 3)>3rd Year</option>
                                <option value="4" @selected(old('year_level') == 4)>4th Year</option>
                                <option value="5" @selected(old('year_level') == 5)>5th Year</option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#8B9690] pointer-events-none">
                                <i class="ph ph-caret-down text-base"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Password & Confirm Password -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label for="password" class="text-sm font-semibold text-[#14231D] block">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#8B9690] pointer-events-none">
                                <i class="ph ph-lock text-lg"></i>
                            </span>
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                placeholder="Min. 12 characters"
                                class="w-full pl-12 pr-12 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-sm text-[#14231D] placeholder-[#8B9690] focus:outline-none focus:border-[#087443] focus:ring-4 focus:ring-[#087443]/10 transition-all duration-300"
                            >
                            <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#8B9690] hover:text-[#087443] transition-colors">
                                <i :class="showPassword ? 'ph ph-eye-slash' : 'ph ph-eye'" class="text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="space-y-2">
                        <label for="password_confirmation" class="text-sm font-semibold text-[#14231D] block">Confirm Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-[#8B9690] pointer-events-none">
                                <i class="ph ph-shield-check text-lg"></i>
                            </span>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                :type="showConfirmation ? 'text' : 'password'"
                                autocomplete="new-password"
                                required
                                placeholder="Re-enter password"
                                class="w-full pl-12 pr-12 py-3 bg-white border border-[#DDE5E1] rounded-2xl text-sm text-[#14231D] placeholder-[#8B9690] focus:outline-none focus:border-[#087443] focus:ring-4 focus:ring-[#087443]/10 transition-all duration-300"
                            >
                            <button type="button" @click="showConfirmation = !showConfirmation" :aria-label="showConfirmation ? 'Hide password confirmation' : 'Show password confirmation'" class="absolute inset-y-0 right-0 pr-4 flex items-center text-[#8B9690] hover:text-[#087443] transition-colors">
                                <i :class="showConfirmation ? 'ph ph-eye-slash' : 'ph ph-eye'" class="text-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-[#00633E] hover:bg-[#004F32] text-white text-sm font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-[#087443]/15 hover:shadow-xl hover:shadow-[#087443]/20 hover:-translate-y-0.5 transition-all duration-300 active:translate-y-0 mt-2">
                    Create Student Account <i class="ph ph-arrow-right text-base"></i>
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
