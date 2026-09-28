@props(['status'])

@if ($status)
    <x-badge :color="$status->color" {{ $attributes }}>
        {{ $status->name }}
    </x-badge>
@else
    <x-badge color="gray" {{ $attributes }}>Unknown</x-badge>
@endif