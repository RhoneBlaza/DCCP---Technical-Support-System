@props(['priority'])

@php
    $color = match ($priority?->key) {
        'urgent' => 'red',
        'high' => 'orange',
        'normal' => 'blue',
        'low' => 'slate',
        default => 'gray',
    };
@endphp

<x-badge :color="$color" {{ $attributes }}>
    @if ($priority)
        {{ $priority->name }}
    @else
        —
    @endif
</x-badge>