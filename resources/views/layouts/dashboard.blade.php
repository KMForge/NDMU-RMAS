@extends('layouts.app')

@section('content')
    <div class="grid gap-6 md:grid-cols-[16rem_1fr]">
        <aside class="rounded-xl bg-emerald-950 p-5 text-white">{{ config('app.name') }}</aside>
        <section>@yield('dashboard-content')</section>
    </div>
@endsection
