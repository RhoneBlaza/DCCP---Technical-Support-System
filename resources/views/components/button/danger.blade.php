@props(['href' => null])
<x-button type="danger" :href="$href" {{ $attributes }}>{{ $slot }}</x-button>