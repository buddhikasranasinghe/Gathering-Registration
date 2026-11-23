@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'p-6',
])

<section {{ $attributes->merge(['class' => "glass {$padding}"]) }}>
    @if ($title || $subtitle || isset($actions))
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                @if ($title)
                    <h2 class="font-display text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
                        {{ $title }}
                    </h2>
                @endif

                @if ($subtitle)
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    @endif

    {{ $slot }}
</section>
