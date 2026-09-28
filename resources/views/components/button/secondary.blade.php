@props(['href' => null])
<x-button type="secondary" :href="$href" {{ $attributes }}>{{ $slot }}</x-button>