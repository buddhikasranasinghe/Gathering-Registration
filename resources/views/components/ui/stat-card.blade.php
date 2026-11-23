@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'violet',
])

@php
    $tones = [
        'violet' => 'from-indigo-500 to-fuchsia-500',
        'amber' => 'from-amber-400 to-orange-500',
        'emerald' => 'from-emerald-400 to-teal-500',
        'sky' => 'from-sky-400 to-cyan-500',
        'rose' => 'from-rose-400 to-pink-500',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'glass relative overflow-hidden p-5']) }}>
    <span
        aria-hidden="true"
        class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-gradient-to-br {{ $tones[$tone] ?? $tones['violet'] }} opacity-25 blur-2xl"
    ></span>

    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
        {{ $label }}
    </p>

    <p class="mt-2 font-display text-3xl font-semibold tabular-nums text-slate-900 dark:text-white">
        {{ $value }}
    </p>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif
</div>
