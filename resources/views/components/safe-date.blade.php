@props(['date'])

<time datetime="{{ $date?->toIso8601String() }}" title="{{ $date?->format('M d, Y h:i A') }}">
    {{ $date?->format('M d, Y') ?? '—' }}
</time>