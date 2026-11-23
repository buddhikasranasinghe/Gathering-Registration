@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
])

@php
    $classes = trim("btn btn-{$variant}" . ($size !== 'md' ? " btn-{$size}" : ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
