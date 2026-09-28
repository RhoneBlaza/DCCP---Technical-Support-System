@props([
    'name' => '',
    'label' => '',
    'checked' => false,
    'value' => 1,
    'showError' => true,
])

<label class="flex items-start gap-2 cursor-pointer select-none">
    <input
        type="checkbox"
        name="{{ $name }}"
        value="{{ $value }}"
        @checked(old($name, $checked)) 
        {{ $attributes->merge(['class' => 'mt-0.5 h-4 w-4 rounded border-slate-300 text-navy-600 focus:ring-navy-500 focus:ring-2']) }}>
    <span class="text-sm text-slate-700">{{ $label }}</span>
</label>
@if ($showError)
    <x-form.error :name="$name" />
@endif
