@extends('layouts.auth')

@section('auth-content')
<div class="min-h-screen bg-[#f4f7f6] flex items-center justify-center p-6 relative">
    <a href="{{ route('login') }}" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-gray-800 shadow-sm transition-all" aria-label="Back to sign in">
        <i class="ph ph-x text-lg" aria-hidden="true"></i>
    </a>

    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-gray-100 p-8 md:p-10 w-full max-w-[460px]">
        <div class="text-center mb-8 flex flex-col items-center">
            <div class="w-12 h-12 rounded-2xl bg-[#0e5c3a]/10 text-[#0e5c3a] flex items-center justify-center mb-4 text-xl">
                <i class="ph ph-lock-key" aria-hidden="true"></i>
            </div>
            <h1 class="text-2xl font-bold font-heading text-gray-800 mb-1">Reset Password</h1>
            <p class="text-xs text-gray-400">Choose a new password for your account.</p>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-3 mb-5 text-xs text-red-700" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="space-y-2">
                <label for="email" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Email Address</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $email) }}"
                    autocomplete="email"
                    required
                    class="w-full px-4 py-3.5 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                >
            </div>

            <div class="space-y-2">
                <label for="password" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">New Password</label>
                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        autofocus
                        class="w-full px-4 pr-11 py-3.5 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                    >
                    <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600" data-password-toggle data-password-input="password" aria-label="Show password" aria-pressed="false">
                        <i data-password-show-icon class="ph ph-eye text-lg" aria-hidden="true"></i>
                        <i data-password-hide-icon class="ph ph-eye-slash text-lg hidden" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <label for="password_confirmation" class="text-xs font-bold text-gray-600 uppercase tracking-wider block">Confirm New Password</label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="w-full px-4 pr-11 py-3.5 bg-white border border-gray-200 rounded-2xl text-sm text-gray-800 focus:outline-none focus:border-[#0e5c3a] focus:ring-4 focus:ring-[#0e5c3a]/5 transition-all"
                    >
                    <button type="button" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600" data-password-toggle data-password-input="password_confirmation" aria-label="Show password confirmation" aria-pressed="false">
                        <i data-password-show-icon class="ph ph-eye text-lg" aria-hidden="true"></i>
                        <i data-password-hide-icon class="ph ph-eye-slash text-lg hidden" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-4 bg-[#0e5c3a] hover:bg-[#0a4a2e] text-white text-sm font-bold rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-[#0e5c3a]/10 transition-all">
                <i class="ph ph-check-circle text-base" aria-hidden="true"></i>
                Reset Password
            </button>
        </form>
    </div>
</div>
@endsection
