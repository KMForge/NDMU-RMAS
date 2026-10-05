@extends('layouts.auth')

@section('auth-content')
<div class="relative flex min-h-screen w-full items-center justify-center overflow-hidden bg-gradient-to-br from-[#021a10] via-[#063823] to-[#02140c] p-4 text-white selection:bg-[#eebc3f] selection:text-[#073823]">
    <div class="pointer-events-none absolute -right-20 -top-20 h-[480px] w-[480px] rounded-full bg-[#eebc3f]/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -left-20 bottom-0 h-[520px] w-[520px] rounded-full bg-emerald-500/15 blur-3xl"></div>

    <main class="relative z-10 w-full max-w-[480px]">
        <div class="overflow-hidden rounded-[28px] border border-white/10 bg-white p-7 text-slate-800 shadow-2xl sm:p-9">
            <div class="mb-6 flex flex-col items-center text-center">
                <x-ndmu-n-logo size="lg" class="mb-3" />
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-emerald-700">Account security</p>
                <h1 class="mt-1 font-heading text-2xl font-black text-slate-900 sm:text-3xl">Create Your New Password</h1>
                <p class="mt-2 text-sm text-slate-500">For security, replace the temporary password issued by the administrator before accessing your workspace.</p>
            </div>

            @if ($temporaryPasswordExpired)
                <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900" role="alert">
                    This temporary password has expired. Use Forgot Password to receive a secure reset link, or ask the administrator to issue a new temporary password.
                </div>
                <div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-2xl bg-[#0e5c3a] px-4 py-3 text-sm font-black text-white hover:bg-[#0a4a2e]">Sign Out, Then Use Forgot Password</button>
                    </form>
                </div>
            @else
                @if ($errors->any())
                    <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-sm font-semibold text-rose-700" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('password.change-required.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="space-y-1.5">
                        <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700">New Password</label>
                        <input id="password" name="password" type="password" required autofocus autocomplete="new-password" minlength="12" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:bg-white focus:ring-[#0e5c3a]" placeholder="At least 12 characters">
                    </div>

                    <div class="space-y-1.5">
                        <label for="password_confirmation" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" minlength="12" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-[#0e5c3a] focus:bg-white focus:ring-[#0e5c3a]" placeholder="Re-enter your new password">
                    </div>

                    <p class="text-xs leading-5 text-slate-500">Use at least 12 characters with uppercase, lowercase, number, and symbol. Do not reuse the temporary password.</p>

                    <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-[#073823] to-[#0e5c3a] px-4 py-3.5 text-sm font-black text-white shadow-lg hover:brightness-110">Save Password &amp; Continue</button>
                </form>
            @endif
        </div>
    </main>
</div>
@endsection
