@props(['for', 'required' => false, 'hint' => null])

<label for="{{ $for }}" {{ $attributes->merge(['class' => 'block text-sm font-medium text-slate-700 mb-1']) }}>
    {{ $slot }}
    @if ($required)
        <span class="text-red-500" aria-hidden="true">*</span>
    @endif
</label>
@if ($hint)
    <p class="text-xs text-slate-500 mb-1 -mt-0.5">{{ $hint }}</p>
@endif