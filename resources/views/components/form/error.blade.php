@props(['name' => ''])

@error($name)
    <p class="mt-1 text-xs text-red-600" role="alert" x-cloak x-show="true">{{ $message }}</p>
@enderror