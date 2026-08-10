@extends('layouts.app')

@section('content')
    <div class="mx-auto flex min-h-[75vh] max-w-3xl items-center justify-center">
        <section class="w-full overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-xl shadow-emerald-950/5">
            <div class="bg-gradient-to-br from-[#0e5c3a] to-[#08452b] px-8 py-10 text-white">
                <p class="text-xs font-extrabold uppercase tracking-[0.22em] text-[#f4c542]">NDMU-RMAS Account Access</p>
                <h1 class="mt-3 font-heading text-3xl font-extrabold">Access assignment pending</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-50/85">
                    Your faculty account is active, but no operational role has been assigned yet.
                </p>
            </div>

            <div class="space-y-6 p-8">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950">
                    An administrator must assign a role such as Research Facilitator, Thesis Adviser, Panel Member, or Dean before a workspace becomes available.
                </div>

                <dl class="grid gap-4 rounded-2xl border border-gray-100 bg-gray-50 p-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-gray-400">Name</dt><dd class="mt-1 font-bold text-gray-900">{{ $user->name }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-gray-400">Email</dt><dd class="mt-1 font-bold text-gray-900">{{ $user->email }}</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-gray-400">Account type</dt><dd class="mt-1 font-bold text-gray-900">Faculty</dd></div>
                    <div><dt class="text-xs font-bold uppercase tracking-wider text-gray-400">Assigned roles</dt><dd class="mt-1 font-bold text-gray-900">None</dd></div>
                </dl>

                <form method="POST" action="{{ route('logout') }}" data-logout-form>
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#0e5c3a] px-5 py-3 text-sm font-bold text-white hover:bg-[#0a4a2e]">
                        <i class="ph ph-sign-out"></i> Logout
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection
