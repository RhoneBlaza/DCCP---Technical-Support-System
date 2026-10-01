@props(['id', 'title' => '', 'wide' => false])

<div
    x-show="$store.modals && $store.modals.isOpen('{{ $id }}')"
    x-cloak
    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
    x-transition.opacity
    role="dialog"
    aria-modal="true">

    <div class="absolute inset-0 bg-navy-950/60" @click="$store.modals.close('{{ $id }}')" aria-hidden="true"></div>

    <div
        class="relative bg-surface w-full {{ $wide ? 'max-w-2xl' : 'max-w-lg' }} rounded-t-lg sm:rounded-lg shadow-xl max-h-[90vh] flex flex-col"
        @keydown.escape.window="$store.modals.close('{{ $id }}')"
        data-modal-title="{{ $title }}">

        @if ($title)
            <div class="flex items-center justify-between px-5 py-4 border-b border-line-soft">
                <h3 class="text-base font-semibold text-ink">{{ $title }}</h3>
                <button type="button" class="p-1 rounded-lg text-ink-faint hover:bg-surface-sunken" @click="$store.modals.close('{{ $id }}')" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif

        <div class="overflow-y-auto px-5 py-4">
            {{ $slot }}
        </div>
    </div>
</div>