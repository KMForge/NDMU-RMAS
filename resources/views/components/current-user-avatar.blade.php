@props([
    'user' => auth()->user(),
    'rounded' => 'rounded-full',
])

@php
    $displayName = $user?->displayFirstName() ?? 'User';
    $initial = Illuminate\Support\Str::upper(Illuminate\Support\Str::substr($displayName, 0, 1));
    $avatarClasses = ['flex h-full w-full items-center justify-center object-cover text-sm font-black leading-none', $rounded];
@endphp

@if ($user?->profile_photo_path)
    <img
        src="{{ route('profile-photo.show', ['v' => $user->profile_photo_updated_at?->timestamp]) }}"
        alt="{{ $user->name }} profile photo"
        {{ $attributes->class($avatarClasses) }}
    >
@else
    <span aria-hidden="true" {{ $attributes->class($avatarClasses) }}>{{ $initial }}</span>
@endif
