@extends('layouts.auth')

@section('auth-content')
<div class="min-h-screen flex flex-col md:flex-row relative bg-[#f4f7f6]">
    <!-- Left Side: Image Banner & Brand Description -->
    <div class="w-full md:w-[45%] lg:w-[40%] bg-[#0e5c3a] text-white p-8 md:p-16 flex flex-col justify-between relative min-h-[400px] md:min-h-screen overflow-hidden" style="background-image: linear-gradient(180deg, rgba(14, 92, 58, 0.94) 0%, rgba(10, 70, 44, 0.97) 100%), url('{{ asset('images/ndmu.jpg') }}'); background-size: cover; background-position: center;">
        <!-- Logo -->
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/ndmu_logo.png') }}" alt="NDMU Logo" class="h-12 w-auto">
            <div class="flex flex-col leading-none">
                <span class="font-heading font-extrabold text-2xl text-white tracking-tight">NDMU</span>
                <span class="text-[10px] font-bold text-[#eebc3f] tracking-wider uppercase mt-1">Research Management</span>
            </div>
        </div>

        <!-- Banner Text Content -->
        <div class="my-auto py-12 space-y-6">
            <span class="text-xs font-bold tracking-widest text-[#eebc3f] uppercase block">Student Registration</span>
            <h1 class="text-4xl md:text-5xl font-heading font-bold text-white leading-tight">
                Join the NDMU<br>Research Community
            </h1>
            <p class="text-white/80 text-sm md:text-base font-light max-w-sm leading-relaxed">
                Create your student researcher account and start your research journey at Notre Dame of Marbel University.
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

            <!-- Note Alert -->
            <div class="border border-[#eebc3f]/20 bg-[#eebc3f]/5 rounded-2xl p-4 mt-4 max-w-sm">
                <p class="text-xs text-white/90 leading-relaxed">
                    <span class="font-bold text-[#eebc3f]">Note:</span> Student registrations are subject to administrator approval. You will be notified via email once your account is activated (typically 1–3 business days).
                </p>
            </div>
        </div>

        <!-- Bottom spacer/branding link -->
        <div class="text-xs text-white/50 font-medium">
            © 2026 Notre Dame of Marbel University.
        </div>
    </div>

    <!-- Right Side: Register Form Card -->
    <div class="w-full md:w-[55%] lg:w-[60%] flex items-center justify-center p-6 md:p-12 relative min-h-screen bg-gradient-to-tr from-[#f0f4f2] to-[#f4f7f6]">
        <!-- Close Button -->
        <a href="{{ url('/') }}" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-white border border-gray-150 flex items-center justify-center text-gray-500 hover:text-gray-800 shadow-sm transition-all duration-300 z-10 hover:scale-105">
            <i class="ph ph-x text-lg"></i>
        </a>

        <!-- Register Card -->
        <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-gray-100/50 p-6 md:p-8 w-full max-w-[580px] flex flex-col relative my-4">
            <!-- Tabs -->
            <div class="flex bg-gray-100/60 p-1.5 rounded-2xl mb-5">
                <a href="{{ route('login') }}" class="flex-1 flex items-center justify-center gap-2 py-2.5 text-xs font-bold rounded-xl text-gray-500 hover:text-gray-800 transition-all duration-300">
                    <i class="ph ph-sign-in text-base"></i> Sign In
                </a>
                <a href="{{ route('register') }}" class="flex-1 flex items-center justify-center gap-2 py-2.5 text-xs font-bold rounded-xl bg-[#0e5c3a] text-white shadow-sm transition-all duration-300">
                    <i class="ph ph-user-plus text-base"></i> Register
                </a>
            </div>

            <!-- Header Icon & Text -->
            <div class="text-center mb-5 flex flex-col items-center">
                <div class="w-10 h-10 rounded-2xl bg-[#0e5c3a]/10 text-[#0e5c3a] flex items-center justify-center mb-3 text-lg">
                    <i class="ph ph-user-plus"></i>
                </div>
                <h2 class="text-xl font-bold font-heading text-gray-800 mb-0.5">Student Registration</h2>
                <p class="text-xs text-gray-400 font-light">Create your NDMU Research Portal account</p>
            </div>

            <!-- Form -->
            <form class="space-y-3 mb-4">
                <!-- Row 1: Student ID & Full Name -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Student ID Field -->
                    <div class="space-y-1">
                        <label for="student_id" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Student ID</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-identification-card text-lg"></i>
                            </span>
                            <input
                                id="student_id"
                                type="text"
                                placeholder="e.g. STU-2026-0001"
                                class="w-full pl-11 pr-3 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                            >
                        </div>
                    </div>

                    <!-- Full Name Field -->
                    <div class="space-y-1">
                        <label for="full_name" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Full Name</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-user text-lg"></i>
                            </span>
                            <input
                                id="full_name"
                                type="text"
                                placeholder="Juan Dela Cruz"
                                class="w-full pl-11 pr-3 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                            >
                        </div>
                    </div>
                </div>

                <!-- Institutional Email Field -->
                <div class="space-y-1">
                    <label for="email" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Institutional Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                            <i class="ph ph-envelope-simple text-lg"></i>
                        </span>
                        <input
                            id="email"
                            type="email"
                            placeholder="juan.delacruz@ndmu.edu.ph"
                            class="w-full pl-11 pr-3 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                        >
                    </div>
                </div>

                <!-- Row 2: Program & Year Level -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Program / Degree Field -->
                    <div class="space-y-1">
                        <label for="program" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Program / Degree</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-book-open text-lg"></i>
                            </span>
                            <select
                                id="program"
                                name="program"
                                class="w-full pl-11 pr-10 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300 appearance-none"
                            >
                                <option value="" disabled selected>Select program</option>
                                @foreach (config('academic.programs') as $program)
                                    <option value="{{ $program['label'] }}">{{ $program['label'] }}</option>
                                @endforeach
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-caret-down text-base"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Year Level Field -->
                    <div class="space-y-1">
                        <label for="year_level" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Year Level</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-graduation-cap text-lg"></i>
                            </span>
                            <select
                                id="year_level"
                                class="w-full pl-11 pr-10 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300 appearance-none"
                            >
                                <option value="" disabled selected>Select level</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                                <option value="5">5th Year</option>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-caret-down text-base"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Password & Confirm Password -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Password Field -->
                    <div class="space-y-1">
                        <label for="password" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-lock text-lg"></i>
                            </span>
                            <input
                                id="password"
                                type="password"
                                placeholder="Min. 8 chars"
                                class="w-full pl-11 pr-11 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                            >
                            <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="ph ph-eye text-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="space-y-1">
                        <label for="password_confirmation" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Confirm Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                                <i class="ph ph-lock text-lg"></i>
                            </span>
                            <input
                                id="password_confirmation"
                                type="password"
                                placeholder="Re-enter password"
                                class="w-full pl-11 pr-11 py-3 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all duration-300"
                            >
                            <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="ph ph-eye text-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="button" class="w-full py-4 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-sm font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-[#0e5c3a]/10 hover:shadow-xl transition-all duration-300">
                    <i class="ph ph-user-plus text-base"></i> Submit Registration
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
