@props(['role', 'label' => null])

@php
    $classes = match ($role) {
        \App\Identity\Entities\UserRole::SuperAdmin => 'bg-violet-500/15 text-violet-700 ring-violet-500/30 dark:bg-violet-400/15 dark:text-violet-200 dark:ring-violet-400/30',
        \App\Identity\Entities\UserRole::MainTreasurer => 'bg-emerald-500/15 text-emerald-700 ring-emerald-500/30 dark:bg-emerald-400/15 dark:text-emerald-200 dark:ring-emerald-400/30',
        \App\Identity\Entities\UserRole::Admin => 'bg-sky-500/15 text-sky-700 ring-sky-500/30 dark:bg-sky-400/15 dark:text-sky-200 dark:ring-sky-400/30',
        default => 'bg-slate-500/10 text-slate-600 ring-slate-500/20 dark:bg-slate-400/10 dark:text-slate-300 dark:ring-slate-400/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset backdrop-blur {$classes}"]) }}>
    {{ $label ?? $role->label() }}
</span>
