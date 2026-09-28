@props(['ticket'])

@php
    $sla = app(\App\Services\SlaCalculator::class);
    $label = $sla->displayLabel($ticket);
    $overdue = $sla->isOverdue($ticket);
    $paused = $sla->isPaused($ticket);
    $outcome = $sla->finishedOutcome($ticket);
    $color = match (true) {
        $outcome === 'breached' => 'red',
        $outcome === 'met' => 'green',
        $overdue => 'red',
        $paused => 'yellow',
        default => 'gray',
    };
@endphp

<x-badge :color="$color" {{ $attributes }}>
    @if ($overdue)
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    @elseif ($paused)
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    @endif
    {{ $label }}
</x-badge>