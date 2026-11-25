@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'back' => null,
    'backLabel' => 'Back',
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="link text-sm">&larr; {{ $backLabel }}</a>
        @endif

        @if ($eyebrow)
            <p @class([
                'text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-violet-700 dark:text-violet-300',
                'mt-3' => $back,
            ])>
                {{ $eyebrow }}
            </p>
        @endif

        <h1 @class([
            'font-display text-3xl font-semibold tracking-tight text-slate-900 dark:text-white',
            'mt-1' => $eyebrow || $back,
        ])>
            {{ $title }}
        </h1>

        @if ($description)
            <p class="mt-1 max-w-2xl text-sm text-slate-600 dark:text-slate-400">
                {{ $description }}
            </p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
