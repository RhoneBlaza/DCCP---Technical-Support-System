@props([
    'color' => 'gray',
    'dot' => false,
    'title' => null,
])

@php
    $palette = [
        // Kept as a real palette colour rather than a surface token: the neutral
        // badge has to stay visually distinct from the coloured ones, which
        // BadgeColorContrastTest asserts on.
        'gray' => 'bg-slate-100 dark:bg-slate-500/20 text-ink ring-slate-200 dark:ring-slate-500/30',
        'blue' => 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 ring-blue-200 dark:ring-blue-500/30',
        'indigo' => 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 ring-indigo-200 dark:ring-indigo-500/30',
        'teal' => 'bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-300 ring-teal-200 dark:ring-teal-500/30',
        'green' => 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-300 ring-green-200 dark:ring-green-500/30',
        'yellow' => 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-800 dark:text-yellow-300 ring-yellow-200 dark:ring-yellow-500/30',
        'orange' => 'bg-orange-50 dark:bg-orange-500/10 text-orange-700 dark:text-orange-300 ring-orange-200 dark:ring-orange-500/30',
        'red' => 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-400 ring-red-200 dark:ring-red-500/30',
        'rose' => 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300 ring-rose-200 dark:ring-rose-500/30',
        'purple' => 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300 ring-purple-200 dark:ring-purple-500/30',
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