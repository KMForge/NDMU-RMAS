@props(['title', 'description' => null])

<div>
    <h1 class="text-2xl font-bold font-heading text-gray-800">{{ $title }}</h1>
    @if ($description)
        <p class="text-xs text-gray-500 mt-1">{{ $description }}</p>
    @endif
</div>
