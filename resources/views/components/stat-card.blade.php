@props(['label' => '', 'value' => '', 'color' => 'navy', 'icon' => null, 'suffix' => null])

@php
    $colors = [
        'navy' => 'text-navy-700 dark:text-navy-200 bg-navy-50 dark:bg-navy-500/10',
        'blue' => 'text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-500/10',
        'green' => 'text-green-700 dark:text-green-300 bg-green-50 dark:bg-green-500/10',
        'amber' => 'text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-500/10',
        'red' => 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-500/10',
        'slate' => 'text-ink bg-surface-sunken',
    ];
@endphp

<div class="bg-surface rounded-lg border border-line p-4 sm:p-5">
    <div class="flex items-center gap-3">
        @if ($icon)
            <div class="flex items-center justify-center w-10 h-10 rounded-xl {{ $colors[$color] }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
            </div>
        @endif
        <div class="min-w-0">
            <p class="text-xs font-medium text-ink-subtle uppercase tracking-wide truncate">{{ $label }}</p>
            <p class="text-2xl font-bold text-ink leading-tight">
                {{ $value }}
                @if ($suffix)
                    <span class="text-sm font-medium text-ink-faint">{{ $suffix }}</span>
                @endif
            </p>
        </div>
    </div>
</div>