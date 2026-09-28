@props([
    'color' => 'gray',
    'dot' => false,
    'title' => null,
])

@php
    $palette = [
        'gray' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-200',
        'green' => 'bg-green-50 text-green-700 ring-green-200',
        'yellow' => 'bg-yellow-50 text-yellow-800 ring-yellow-200',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'purple' => 'bg-purple-50 text-purple-700 ring-purple-200',
    ];
    $classes = $palette[$color] ?? $palette['gray'];
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ring-1 ring-inset '.$classes]) }}
    @if ($title) title="{{ $title }}" @endif>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
    @endif
    {{ $slot }}
</span>