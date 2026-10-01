@props([
    'name' => '',
    'value' => null,
    'rows' => 4,
])

@php
    $hasError = $errors->has($name);
@endphp

<textarea
    name="{{ $name }}"
    id="{{ $name }}"
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => 'block w-full rounded-lg border px-3 py-2 text-sm text-ink placeholder:text-ink-faint focus:ring-2 '.($hasError ? 'border-red-400 focus:border-red-500 focus:ring-red-200 dark:focus:ring-red-500/40' : 'border-line-strong focus:border-navy-500 focus:ring-navy-200 dark:focus:ring-navy-500/40'), 'aria-invalid' => $hasError ? 'true' : null]) }}>{{ old($name, $value ?? '') }}</textarea>
<x-form.error :name="$name" />