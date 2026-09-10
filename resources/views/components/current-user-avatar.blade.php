@props([
    'user' => auth()->user(),
    'rounded' => 'rounded-full',
])

@php
    $initial = Illuminate\Support\Str::upper(
        Illuminate\Support\Str::substr($user?->name ?? 'User', 0, 1),
    );
@endphp

@if ($user?->profile_photo_path)
    <img
        src="{{ route('profile-photo.show', ['v' => $user->profile_photo_updated_at?->timestamp]) }}"
        alt="{{ $user->name }} profile photo"
        {{ $attributes->class(['h-full w-full object-cover', $rounded]) }}
    >
@else
    <span aria-hidden="true">{{ $initial }}</span>
@endif
