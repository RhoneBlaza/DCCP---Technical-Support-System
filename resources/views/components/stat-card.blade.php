@props(['label' => '', 'value' => '', 'color' => 'navy', 'icon' => null, 'suffix' => null])

@php
    $colors = [
        'navy' => 'text-navy-700 bg-navy-50',
        'blue' => 'text-blue-700 bg-blue-50',
        'green' => 'text-green-700 bg-green-50',
        'amber' => 'text-amber-700 bg-amber-50',
        'red' => 'text-red-700 bg-red-50',
        'slate' => 'text-slate-700 bg-slate-100',
    ];
@endphp

<div class="bg-white rounded-lg border border-slate-200 p-4 sm:p-5">
    <div class="flex items-center gap-3">
        @if ($icon)
            <div class="flex items-center justify-center w-10 h-10 rounded-xl {{ $colors[$color] }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
            </div>
        @endif
        <div class="min-w-0">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide truncate">{{ $label }}</p>
            <p class="text-2xl font-bold text-slate-800 leading-tight">
                {{ $value }}
                @if ($suffix)
                    <span class="text-sm font-medium text-slate-400">{{ $suffix }}</span>
                @endif
            </p>
        </div>
    </div>
</div>