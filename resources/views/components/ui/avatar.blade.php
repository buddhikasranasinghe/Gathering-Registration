@props(['name' => '', 'size' => 'md'])

@php
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $sizes = [
        'sm' => 'h-8 w-8 text-[0.7rem]',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-14 w-14 text-lg',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 via-violet-500 to-fuchsia-500 font-display font-semibold text-white shadow-md shadow-violet-500/25 ' . ($sizes[$size] ?? $sizes['md'])]) }}>
    {{ $initials !== '' ? $initials : '?' }}
</span>
