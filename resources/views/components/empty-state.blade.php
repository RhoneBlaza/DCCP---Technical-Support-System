@props(['title' => 'Nothing here yet', 'description' => null, 'actionHref' => null, 'actionLabel' => ''])

<div class="text-center py-12 px-4">
    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-surface-sunken text-ink-faint mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V8a5 5 0 00-5-5H8a4 4 0 00-4 4v12a2 2 0 002 2h2"/></svg>
    </div>
    <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
    @if ($description)
        <p class="text-sm text-ink-subtle mt-1 max-w-sm mx-auto">{{ $description }}</p>
    @endif
    @if ($actionHref)
        <div class="mt-4">
            <x-button.primary :href="$actionHref">{{ $actionLabel }}</x-button.primary>
        </div>
    @endif
</div>