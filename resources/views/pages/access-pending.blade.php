@extends('layouts.app')

@section('content')
    <div class="mx-auto flex min-h-[80vh] max-w-2xl items-center justify-center p-4">
        <section class="w-full overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xl shadow-emerald-950/10 relative">
            <!-- Top Accent Stripe -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#073823] via-[#eebc3f] to-[#0e5c3a]"></div>

            <div class="bg-gradient-to-br from-[#073823] via-[#0e5c3a] to-[#042416] px-8 py-10 text-white relative">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/25 backdrop-blur-md border border-[#eebc3f]/30 text-[#eebc3f] font-black text-[10px] uppercase tracking-[0.16em]">
                        <i class="ph ph-shield-warning text-xs"></i>
                        NDMU-RMAS Account Access
                    </span>
                </div>
                <h1 class="mt-3 font-heading text-2xl sm:text-3xl font-black tracking-tight text-white">Access Assignment Pending</h1>
                <p class="mt-2 text-xs sm:text-sm leading-relaxed text-emerald-100/85 max-w-xl">
                    Your institutional account is verified, but an operational research role has not been designated yet.
                </p>
            </div>

            <div class="space-y-6 p-7 sm:p-8">
                <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-xs font-medium text-amber-950 flex items-start gap-3">
                    <i class="ph ph-info text-lg text-amber-700 shrink-0 mt-0.5"></i>
                    <p class="leading-relaxed">A system administrator must assign a research role (such as <strong>Research Facilitator</strong>, <strong>Thesis Adviser</strong>, <strong>Panelist</strong>, or <strong>Dean</strong>) before your portal workspace activates.</p>
                </div>

                <dl class="grid gap-4 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-5 sm:grid-cols-2 text-xs">
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Account Name</dt>
                        <dd class="mt-1 font-bold text-slate-900">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Email Address</dt>
                        <dd class="mt-1 font-bold text-slate-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Account Type</dt>
                        <dd class="mt-1 font-bold text-[#0e5c3a]">Institutional Faculty</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-wider text-slate-400">Assigned Roles</dt>
                        <dd class="mt-1 font-bold text-amber-700">None Assigned</dd>
                    </div>
                </dl>

                <div class="pt-2 flex justify-end">
                    <form method="POST" action="{{ route('logout') }}" data-logout-form>
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#0e5c3a] hover:bg-[#073823] px-5 py-2.5 text-xs font-bold text-white shadow-md transition-colors cursor-pointer">
                            <i class="ph ph-sign-out text-sm"></i>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection

