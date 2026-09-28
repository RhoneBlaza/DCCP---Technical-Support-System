@props([
    'name' => '',
    'type' => 'text',
    'value' => null,
])

@php
    $hasError = $errors->has($name);
@endphp

<input
    type="{{ $type }}"
    name="{{ $name }}"
    id="{{ $name }}"
    value="{{ old($name, $value ?? '') }}"
    {{ $attributes->merge(['class' => 'block w-full rounded-lg border px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-2 '.($hasError ? 'border-red-400 focus:border-red-500 focus:ring-red-200' : 'border-slate-300 focus:border-navy-500 focus:ring-navy-200'), 'aria-invalid' => $hasError ? 'true' : null]) }}>
<x-form.error :name="$name" />