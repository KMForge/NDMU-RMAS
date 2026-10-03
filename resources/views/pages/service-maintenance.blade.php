@extends('layouts.auth')

@section('auth-content')
    <div class="flex min-h-[calc(100vh-5rem)] items-center justify-center px-4 py-10">
        <section class="w-full max-w-2xl overflow-hidden rounded-[2rem] border border-amber-200 bg-white shadow-2xl shadow-emerald-950/10">
            <div class="bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#286b49] px-7 py-8 text-white sm:px-10">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#eebc3f] text-2xl text-[#073823] shadow-lg">
                        <i class="ph ph-wrench"></i>
                    </span>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#f6cf69]">Temporary service maintenance</p>
                        <h1 class="mt-1 font-heading text-2xl font-black sm:text-3xl">{{ $serviceName }}</h1>
                    </div>
                </div>
            </div>

            <div class="px-7 py-8 sm:px-10">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950">
                    {{ $maintenanceMessage }}
                </div>
                <p class="mt-5 text-sm leading-6 text-slate-600">No action is required. Please try again after the system administrator restores this service.</p>

                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#0e5c3a] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#073823]">
                        <i class="ph ph-sign-in"></i> Sign In
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        <i class="ph ph-house"></i> Return to Home
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
