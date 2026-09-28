@props([
    'type' => 'primary',
    'href' => null,
    'loading' => false,
])

@php
    $styles = [
        'primary' => 'bg-navy-700 text-white hover:bg-navy-800 focus-visible:ring-navy-500',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50 focus-visible:ring-navy-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500',
    ];
    $base = 'inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base.' '.$styles[$type]]) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => $base.' '.$styles[$type]]) }}>{{ $slot }}</button>
@endif