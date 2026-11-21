@props(['role'])

@php
    $classes = match ($role) {
        \App\Identity\Entities\UserRole::SuperAdmin => 'bg-violet-50 text-violet-700 ring-violet-200',
        \App\Identity\Entities\UserRole::Admin => 'bg-sky-50 text-sky-700 ring-sky-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>
    {{ $role->label() }}
</span>
