@props(['type' => 'success', 'dismissible' => false])

@php
    $styles = [
        'success' => 'bg-green-50 dark:bg-green-500/10 text-green-800 dark:text-green-300 ring-green-200 dark:ring-green-500/30',
        'error' => 'bg-red-50 dark:bg-red-500/10 text-red-800 dark:text-red-300 ring-red-200 dark:ring-red-500/30',
        'warning' => 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-800 dark:text-yellow-300 ring-yellow-200 dark:ring-yellow-500/30',
        'info' => 'bg-navy-50 dark:bg-navy-500/10 text-navy-800 dark:text-navy-100 ring-navy-200 dark:ring-navy-500/30',
    ];
    $icons = [
        'success' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'error' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'warning' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ];
@endphp

<div
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl ring-1 px-4 py-3 text-sm '.$styles[$type]]) }}
    @if ($dismissible) data-auto-dismiss="5" @endif
    role="alert">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$type] }}"/></svg>
    <div class="flex-1">{{ $slot }}</div>
</div>