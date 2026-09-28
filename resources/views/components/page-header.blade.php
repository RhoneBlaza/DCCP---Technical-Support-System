@props(['title' => '', 'description' => null, 'breadcrumb' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-x-6 gap-y-4']) }}>
    <div class="min-w-0">
        @if ($breadcrumb)
            <nav class="flex items-center gap-1.5 text-xs text-slate-400 mb-1.5" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}" class="font-medium hover:text-navy-700">Home</a>
                <span aria-hidden="true">/</span>
                <span class="font-medium text-slate-600">{{ $breadcrumb }}</span>
            </nav>
        @endif
        <h1 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">{{ $title }}</h1>
        @if ($description)
            <p class="text-sm text-slate-500 mt-1">{{ $description }}</p>
        @endif
    </div>
    @if (isset($action) || isset($actions))
        <div class="flex items-center gap-2 shrink-0">
            {{ $action ?? $actions }}
        </div>
    @endif
</div>