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
        {{ $attributes->merge(['class' => 'mt-0.5 h-4 w-4 rounded border-line-strong text-navy-600 dark:text-navy-300 focus:ring-navy-500 dark:focus:ring-navy-400 focus:ring-2']) }}>
    <span class="text-sm text-ink">{{ $label }}</span>
</label>
@if ($showError)
    <x-form.error :name="$name" />
@endif
