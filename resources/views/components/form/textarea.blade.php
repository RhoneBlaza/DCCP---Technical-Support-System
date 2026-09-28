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
    {{ $attributes->merge(['class' => 'block w-full rounded-lg border px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-2 '.($hasError ? 'border-red-400 focus:border-red-500 focus:ring-red-200' : 'border-slate-300 focus:border-navy-500 focus:ring-navy-200'), 'aria-invalid' => $hasError ? 'true' : null]) }}>{{ old($name, $value ?? '') }}</textarea>
<x-form.error :name="$name" />