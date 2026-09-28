@props([
    'except' => [],
])

@php($visibleErrors = collect($errors->all())->except($except)->flatten()->values())

@if ($visibleErrors->isNotEmpty())
    <div class="rounded-xl bg-red-50 ring-1 ring-red-200 px-4 py-3 text-sm text-red-700" role="alert">
        <p class="font-semibold mb-1">There were errors with your submission:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($visibleErrors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
