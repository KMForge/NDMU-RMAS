@extends('layouts.auth')

@section('auth-content')
<div class="min-h-screen bg-[#f4f7f6] flex items-center justify-center p-6 relative">
    <a href="{{ route('login') }}" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-gray-800 shadow-sm transition-all" aria-label="Back to sign in">
        <i class="ph ph-x text-lg" aria-hidden="true"></i>
    </a>

    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-gray-100 p-8 md:p-10 w-full max-w-[460px]">
        <div class="text-center mb-8 flex flex-col items-center">
            <div class="w-12 h-12 rounded-2xl bg-[#0e5c3a]/10 text-[#0e5c3a] flex items-center justify-center mb-4 text-xl">
                <i class="ph ph-key" aria-hidden="true"></i>
            </div>
            <h1 class="text-2xl font-bold font-heading text-gray-800 mb-1">Forgot Password</h1>
            <p class="text-xs text-gray-400 leading-relaxed">Enter your account email and we will send a secure reset link if the account exists.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3 mb-5 text-xs text-emerald-700" role="status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <div class="space-y-2">
                <label for="email" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Email Address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 pointer-events-none">
                        <i class="ph ph-envelope-simple text-lg" aria-hidden="true"></i>
                    </span>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                        placeholder="your.email@ndmu.edu.ph"
                        class="w-full pl-11 pr-4 py-3.5 bg-white border @error('email') border-red-300 @else border-gray-200 @enderror rounded-2xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                    >
                </div>
                @error('email')
                    <p class="text-xs text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full py-4 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-sm font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-[#0e5c3a]/10 transition-all">
                <i class="ph ph-paper-plane-tilt text-base" aria-hidden="true"></i>
                Send Reset Link
            </button>
        </form>

        <a href="{{ route('login') }}" class="mt-6 flex items-center justify-center gap-2 text-xs font-semibold text-[#0e5c3a] hover:underline">
            <i class="ph ph-arrow-left" aria-hidden="true"></i>
            Back to Sign In
        </a>
    </div>
</div>
@endsection
