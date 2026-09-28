@props(['title' => null, 'description' => null, 'flush' => false])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-slate-200']) }}>
    @if ($title)
        <div class="flex items-start justify-between gap-3 px-4 sm:px-5 py-3.5 border-b border-slate-100">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-slate-800">{{ $title }}</h2>
                @if ($description)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="shrink-0">{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div @class(['p-4 sm:p-5' => ! $flush])>
        {{ $slot }}
    </div>
</div>