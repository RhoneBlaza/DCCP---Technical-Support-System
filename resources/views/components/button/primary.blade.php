@props(['href' => null])
<x-button type="primary" :href="$href" {{ $attributes }}>{{ $slot }}</x-button>