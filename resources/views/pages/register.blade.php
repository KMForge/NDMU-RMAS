@extends('layouts.auth')

@section('auth-content')
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
        <div class="bg-[#0f3d24] px-8 py-10 text-white">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-[#eebc3f]">NDMU RMAS</p>
            <h1 class="mt-3 text-3xl font-bold">Create Account</h1>
            <p class="mt-3 text-sm text-emerald-50/90">
                Start your research journey with a dedicated student or faculty account.
            </p>
        </div>

        <div class="space-y-6 px-8 py-8">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                This is a UI placeholder. Registration handling still needs backend implementation.
            </div>

            <form class="space-y-5">
                <div>
                    <label for="full_name" class="mb-2 block text-sm font-semibold text-slate-700">Full name</label>
                    <input
                        id="full_name"
                        type="text"
                        placeholder="Enter your full name"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-[#0f3d24] focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label for="register_email" class="mb-2 block text-sm font-semibold text-slate-700">Email address</label>
                    <input
                        id="register_email"
                        type="email"
                        placeholder="name@ndmu.edu.ph"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-[#0f3d24] focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div>
                    <label for="role" class="mb-2 block text-sm font-semibold text-slate-700">Role</label>
                    <select
                        id="role"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-[#0f3d24] focus:ring-4 focus:ring-emerald-100"
                    >
                        <option>Student Researcher</option>
                        <option>Faculty Adviser</option>
                        <option>Research Staff</option>
                    </select>
                </div>

                <div>
                    <label for="register_password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
                    <input
                        id="register_password"
                        type="password"
                        placeholder="Create a password"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-[#0f3d24] focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <button
                    type="button"
                    class="w-full rounded-2xl bg-[#0f3d24] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#0a2e1b]"
                >
                    Register
                </button>
            </form>

            <p class="text-center text-sm text-slate-600">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-[#0f3d24] hover:text-[#0a2e1b]">Go to login</a>
            </p>
        </div>
    </div>
@endsection
