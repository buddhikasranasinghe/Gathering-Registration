@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'bg-slate-500/10 text-slate-700 ring-slate-500/20 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/20',
        'violet' => 'bg-violet-500/12 text-violet-700 ring-violet-500/25 dark:bg-violet-400/15 dark:text-violet-200 dark:ring-violet-400/25',
        'sky' => 'bg-sky-500/12 text-sky-700 ring-sky-500/25 dark:bg-sky-400/15 dark:text-sky-200 dark:ring-sky-400/25',
        'emerald' => 'bg-emerald-500/12 text-emerald-700 ring-emerald-500/25 dark:bg-emerald-400/15 dark:text-emerald-200 dark:ring-emerald-400/25',
        'amber' => 'bg-amber-500/15 text-amber-800 ring-amber-500/30 dark:bg-amber-400/15 dark:text-amber-200 dark:ring-amber-400/25',
        'rose' => 'bg-rose-500/12 text-rose-700 ring-rose-500/25 dark:bg-rose-400/15 dark:text-rose-200 dark:ring-rose-400/25',
    ];

    $toneClasses = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {$toneClasses}"]) }}>
    {{ $slot }}
</span>
