@props([
    // 'floating' pins the toggle to the corner of guest pages, where there is no
    // top bar to put it in.
    'placement' => 'inline',
])

@php
    $options = \App\Enums\ThemePreference::cases();
@endphp

{{-- The choice lives in the theme store, so this control stays in sync with any
     other one on the page and with the OS while "System" is selected. --}}
<div
    @class([
        'fixed right-4 top-4 z-40' => $placement === 'floating',
        'relative' => $placement !== 'floating',
    ])
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @click.outside="open = false">
    <button
        type="button"
        class="p-2 rounded-lg text-ink-muted hover:bg-surface-sunken focus-visible:ring-2 focus-visible:ring-navy-500 dark:focus-visible:ring-navy-400 focus-visible:ring-offset-2"
        @click="open = ! open"
        aria-haspopup="true"
        aria-controls="theme-menu"
        :aria-expanded="open.toString()"
        :aria-label="'Theme: ' + $store.theme.label()">
        <span x-cloak x-show="$store.theme.is('light')" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5m-15 0H3m15.364 6.364l-1.06-1.06M6.696 17.304l-1.06-1.06m12.728 0l-1.06 1.06M6.696 6.696l-1.06 1.06M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
        </span>
        <span x-cloak x-show="$store.theme.is('dark')" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
        </span>
        <span x-cloak x-show="$store.theme.is('system')" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20.25m0 0l-.003.003m.003-.003l.675.18a2.25 2.25 0 002.226-1.662M12 18h.008v.008H12V18zm0 0v3.75m0-3.75h3.75M15 12h3.75m-3.75 0h3.75m-3.75 0V8.25m0 3.75V15M12 8.25H8.25M12 8.25H15.75M12 8.25v3.75M4.5 12.75h15a2.25 2.25 0 012.25 2.25V21A2.25 2.25 0 0119.5 23.25h-15A2.25 2.25 0 012.25 21v-6A2.25 2.25 0 014.5 12.75z"/></svg>
        </span>
    </button>

    <div
        id="theme-menu"
        x-cloak
        x-show="open"
        x-transition.opacity
        role="menu"
        aria-label="Colour theme"
        class="absolute right-0 mt-2 w-48 rounded-xl bg-surface ring-1 ring-line shadow-lg py-1 z-40">
        @foreach ($options as $option)
            <button
                type="button"
                role="menuitemradio"
                :aria-checked="$store.theme.is('{{ $option->value }}').toString()"
                @click="$store.theme.set('{{ $option->value }}'); open = false"
                class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-ink-muted hover:bg-surface-muted"
                :class="$store.theme.is('{{ $option->value }}') ? '!bg-surface-sunken !text-ink' : ''">
                <span class="shrink-0" aria-hidden="true">
                    @if ($option === \App\Enums\ThemePreference::Light)
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5m-15 0H3m15.364 6.364l-1.06-1.06M6.696 17.304l-1.06-1.06m12.728 0l-1.06 1.06M6.696 6.696l-1.06 1.06M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                    @elseif ($option === \App\Enums\ThemePreference::Dark)
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20.25m0 0l-.003.003m.003-.003l.675.18a2.25 2.25 0 002.226-1.662M12 18h.008v.008H12V18zm0 0v3.75m0-3.75h3.75M15 12h3.75m-3.75 0h3.75m-3.75 0V8.25m0 3.75V15M12 8.25H8.25M12 8.25H15.75M12 8.25v3.75M4.5 12.75h15a2.25 2.25 0 012.25 2.25V21A2.25 2.25 0 0119.5 23.25h-15A2.25 2.25 0 012.25 21v-6A2.25 2.25 0 014.5 12.75z"/></svg>
                    @endif
                </span>
                <span class="flex-1 text-left">{{ $option->label() }}</span>
                <span x-cloak x-show="$store.theme.is('{{ $option->value }}')" class="text-navy-600 dark:text-navy-300 shrink-0" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </span>
            </button>
        @endforeach
    </div>
</div>
