<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="NDMU Research Management System for submissions, reviews, defenses, revisions, and archiving.">
    <title>NDMU Research Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body{font-family:'Instrument Sans',ui-sans-serif,system-ui,sans-serif}.hero-grid{background-color:#064b35;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:42px 42px}.paper-lines{background-image:repeating-linear-gradient(to bottom,transparent 0,transparent 24px,rgba(7,94,61,.07) 25px)}
    </style>
</head>
<body class="m-0 overflow-x-hidden bg-[#f5f3ea] text-[#17372c] antialiased">
<header class="sticky top-0 z-50 border-b border-[#075e3d]/10 bg-white/95 backdrop-blur-sm">
    <div class="mx-auto flex min-h-20 max-w-[1440px] items-center gap-3 px-4 py-3 sm:px-6 lg:grid lg:grid-cols-[1fr_auto_1fr] lg:px-10">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5" aria-label="NDMU Research Management home">
            <img src="{{ asset('images/ndmu-logo-small.png') }}" alt="Notre Dame of Marbel University seal" width="52" height="52" class="h-11 w-11 shrink-0 object-contain sm:h-12 sm:w-12">
            <span class="min-w-0 border-l border-[#075e3d]/15 pl-2.5 leading-none"><strong class="block text-base font-black tracking-tight text-[#075e3d] sm:text-lg">NDMU</strong><span class="mt-1 block text-[8px] font-extrabold uppercase tracking-[.15em] text-[#a27608] sm:text-[9px]">Research Management</span></span>
        </a>
        <div class="hidden text-center lg:block"><p class="text-[10px] font-bold uppercase tracking-[.2em] text-[#a27608]">University Information System</p><p class="mt-0.5 text-sm font-extrabold text-[#075e3d] xl:text-base">NDMU Research Management System</p></div>
        <div class="ml-auto flex shrink-0 items-center gap-2 lg:justify-self-end">
            @if(Route::has('login'))<a href="{{ route('login') }}" class="rounded-lg border border-[#075e3d]/35 bg-white px-3 py-2 text-xs font-bold text-[#075e3d] transition hover:bg-[#f5f3ea] sm:px-5 sm:text-sm">Login</a>@endif
            @if(Route::has('register'))<a href="{{ route('register') }}" class="rounded-lg border border-[#075e3d] bg-[#075e3d] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#064b35] sm:px-5 sm:text-sm">Register</a>@endif
        </div>
    </div>
    <div class="border-t border-[#075e3d]/8 px-4 py-2 text-center lg:hidden"><p class="text-[10px] font-extrabold uppercase tracking-[.14em] text-[#075e3d]">NDMU Research Management System</p></div>
</header>

<main>
<section class="hero-grid relative overflow-hidden text-white">
    <div class="pointer-events-none absolute -right-28 -top-28 h-96 w-96 rounded-full border-[70px] border-[#e8b923]/5"></div>
    <div class="mx-auto grid min-h-[650px] max-w-7xl items-center gap-16 px-6 py-20 lg:grid-cols-[1.03fr_.97fr] lg:px-8 lg:py-24">
        <div class="relative z-10 max-w-2xl">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-[#e8b923]/45 bg-[#053f2d] px-3.5 py-2 text-[10px] font-extrabold uppercase tracking-[.18em] text-[#f3cf5d] sm:text-xs"><span class="h-2 w-2 rounded-full bg-[#e8b923]"></span>Internal · NDMU Research Management</div>
            <h1 class="text-4xl font-black leading-[1.08] tracking-[-.035em] sm:text-5xl lg:text-[3.8rem]">Manage research from <span class="text-[#f0c83d]">proposal</span> to institutional <span class="text-[#f0c83d]">archiving.</span></h1>
            <p class="mt-6 max-w-xl text-base leading-7 text-[#d6e3dc] sm:text-lg">NDMU Research Management System centralizes research submissions, reviews, approvals, defenses, revisions, and institutional archiving in one secure platform.</p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                @if(Route::has('login'))<a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-[#e8b923] px-6 text-sm font-extrabold text-[#17372c] transition hover:bg-[#f2ca42]">Log in to Continue <i class="ph ph-arrow-right"></i></a>@endif
                @if(Route::has('register'))<a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg border border-white/45 bg-white/5 px-6 text-sm font-bold text-white transition hover:bg-white/10">Create an Account</a>@endif
            </div>
        </div>
        <div class="relative mx-auto w-full max-w-[540px] pb-7 pt-5 lg:mx-0 lg:justify-self-end" aria-label="Sample research record under review">
            <div class="absolute inset-x-8 bottom-0 top-16 rotate-3 rounded-2xl bg-[#d7a916] shadow-2xl"></div><div class="absolute inset-x-4 bottom-4 top-9 -rotate-2 rounded-2xl border border-[#d9ddcf] bg-[#e9e7dc]"></div>
            <article class="paper-lines relative rounded-2xl border border-white/80 bg-[#fffefa] p-5 text-[#17372c] shadow-[0_28px_70px_rgba(0,0,0,.3)] sm:p-7">
                <div class="flex items-start justify-between gap-4 border-b border-[#075e3d]/10 pb-5"><div><p class="text-[10px] font-extrabold uppercase tracking-[.2em] text-[#8c6b12]">Research Record</p><p class="mt-1 font-mono text-xs font-bold text-[#075e3d] sm:text-sm">RES-2026-00417</p></div><span class="rounded-full border border-[#d5a711]/30 bg-[#f7e9ae] px-3 py-1.5 text-[9px] font-black tracking-[.12em] text-[#745600] sm:text-[10px]">UNDER REVIEW</span></div>
                <div class="py-6"><p class="text-[10px] font-bold uppercase tracking-[.14em] text-[#728078]">Research title</p><h2 class="mt-2 text-xl font-black leading-snug text-[#064b35] sm:text-2xl">Development of a Research Management Information System</h2><div class="mt-5 grid grid-cols-2 gap-4"><div><p class="text-[9px] font-bold uppercase tracking-wider text-[#89938d]">Submitted by</p><p class="mt-1 text-xs font-bold sm:text-sm">Research Group</p></div><div><p class="text-[9px] font-bold uppercase tracking-wider text-[#89938d]">College / Program</p><p class="mt-1 text-xs font-bold sm:text-sm">NDMU Academic Unit</p></div></div></div>
                <div class="border-t border-[#075e3d]/10 pt-5"><div class="relative flex justify-between before:absolute before:left-[7%] before:right-[7%] before:top-3 before:h-px before:bg-[#d5d8d0]">
                    @foreach([['check','Title Proposal','done'],['user-check','Adviser Review','done'],['presentation-chart','Proposal Defense','current'],['seal-check','Final Defense','next'],['archive','Archiving','next']] as $stage)
                    <div class="relative z-10 flex w-1/5 flex-col items-center text-center"><span class="flex h-6 w-6 items-center justify-center rounded-full border text-[10px] {{ $stage[2]==='done'?'border-[#075e3d] bg-[#075e3d] text-white':($stage[2]==='current'?'border-[#e8b923] bg-[#e8b923] text-[#17372c] ring-4 ring-[#e8b923]/15':'border-[#c9cec7] bg-[#f5f3ea] text-[#929b95]') }}"><i class="ph ph-{{ $stage[0] }}"></i></span><span class="mt-2 hidden text-[8px] font-bold leading-tight text-[#66736c] sm:block">{{ $stage[1] }}</span></div>
                    @endforeach
                </div></div>
            </article>
        </div>
    </div>
</section>

<section class="bg-[#f5f3ea] py-20 sm:py-24"><div class="mx-auto max-w-7xl px-6 lg:px-8">
    <div class="max-w-2xl"><p class="text-xs font-extrabold uppercase tracking-[.2em] text-[#a27608]">Research lifecycle</p><h2 class="mt-3 text-3xl font-black tracking-tight text-[#064b35] sm:text-4xl">How research moves</h2><p class="mt-3 text-[#5e6e66]">One organized process from proposal to institutional archiving.</p></div>
    <div class="relative mt-14 grid gap-8 md:grid-cols-5 md:gap-4 md:before:absolute md:before:left-[9%] md:before:right-[9%] md:before:top-5 md:before:border-t md:before:border-dashed md:before:border-[#c59a19]">
        @foreach([['file-text','Title Proposal','Submit initial research proposal.'],['user-check','Adviser Review','Review, consultation, and revision.'],['presentation','Proposal Defense','Defense evaluation and required revisions.'],['seal-check','Final Defense','Final evaluation and approval.'],['archive','Archiving','Approved research stored in the institutional repository.']] as $index=>$step)
        <article class="relative grid grid-cols-[2.5rem_1fr] gap-4 md:block md:text-center"><span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-4 border-[#f5f3ea] bg-[#075e3d] text-xs font-black text-[#f0c83d] md:mx-auto">{{ str_pad($index+1,2,'0',STR_PAD_LEFT) }}</span><div class="md:mt-5"><i class="ph ph-{{ $step[0] }} text-xl text-[#a27608]"></i><h3 class="mt-1 text-sm font-extrabold text-[#064b35]">{{ $step[1] }}</h3><p class="mx-auto mt-2 max-w-[190px] text-xs leading-5 text-[#68766f]">{{ $step[2] }}</p></div></article>
        @endforeach
    </div>
</div></section>

<section class="border-y border-[#075e3d]/10 bg-[#edeade] py-20 sm:py-24"><div class="mx-auto max-w-7xl px-6 lg:px-8">
    <div class="text-center"><p class="text-xs font-extrabold uppercase tracking-[.2em] text-[#a27608]">One secure workspace</p><h2 class="mt-3 text-3xl font-black tracking-tight text-[#064b35] sm:text-4xl">Everything your research needs</h2><p class="mx-auto mt-3 max-w-xl text-[#5e6e66]">Documents, approvals, revisions, and progress kept in one place.</p></div>
    <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([['chart-line-up','Research Progress Tracking','Track the research from initial proposal through final archiving.'],['file-arrow-up','Document Submission','Securely submit research documents and required revisions.'],['checks','Reviews & Approvals','Keep adviser, facilitator, coordinator, and panel decisions organized.'],['books','Research Archive','Maintain approved research records in the institutional repository.']] as $feature)
        <article class="rounded-2xl border border-[#075e3d]/10 border-t-4 border-t-[#d9aa19] bg-[#fffefa] p-6 shadow-[0_10px_30px_rgba(23,55,44,.06)]"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#075e3d]/8 text-[#075e3d]"><i class="ph ph-{{ $feature[0] }} text-2xl"></i></span><h3 class="mt-5 text-base font-extrabold text-[#064b35]">{{ $feature[1] }}</h3><p class="mt-3 text-sm leading-6 text-[#68766f]">{{ $feature[2] }}</p></article>
        @endforeach
    </div>
</div></section>

<section class="bg-[#075e3d] px-6 py-16 text-center text-white"><h2 class="text-2xl font-black sm:text-3xl">Ready to manage your research?</h2><p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-[#d7e4dd]">Access the NDMU Research Management System using your authorized account.</p>@if(Route::has('login'))<a href="{{ route('login') }}" class="mt-7 inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-[#e8b923] px-6 text-sm font-extrabold text-[#17372c] transition hover:bg-[#f2ca42]">Get Started <i class="ph ph-arrow-right"></i></a>@endif</section>
</main>
<footer class="border-t border-[#075e3d]/10 bg-[#fffefa] px-6 py-6 text-center text-xs text-[#68766f]">&copy; 2026 Notre Dame of Marbel University · Research Management System</footer>
</body></html>
