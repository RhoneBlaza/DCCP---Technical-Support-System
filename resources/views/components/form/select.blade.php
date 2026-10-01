@props([
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
])

@php
    $hasError = $errors->has($name);
    $values = is_array($selected) ? $selected : ($selected !== null ? [(string) $selected] : []);
    $values = array_map('strval', $values);
@endphp

<select
    name="{{ $name }}"
    id="{{ $name }}"
    {{ $attributes->merge(['class' => 'block w-full rounded-lg border px-3 py-2 text-sm text-ink focus:ring-2 '.($hasError ? 'border-red-400 focus:border-red-500 focus:ring-red-200 dark:focus:ring-red-500/40' : 'border-line-strong focus:border-navy-500 focus:ring-navy-200 dark:focus:ring-navy-500/40'), 'aria-invalid' => $hasError ? 'true' : null]) }}>
    @if ($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected(in_array((string) $value, $values, true))>{{ $label }}</option>
    @endforeach
</select>
<x-form.error :name="$name" />