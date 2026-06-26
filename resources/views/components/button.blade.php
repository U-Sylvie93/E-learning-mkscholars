@props([
    'href' => null,
    'variant' => 'primary',
    'size' => 'md',
])

@php
    $base = 'inline-flex items-center justify-center rounded-md text-center font-semibold leading-snug transition mk-focus';
    $sizes = [
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-5 py-3 text-sm',
        'lg' => 'px-6 py-3.5 text-base',
    ];
    $variants = [
        'primary' => 'bg-mk-gold text-mk-navy shadow-sm hover:bg-yellow-300',
        'secondary' => 'bg-white text-mk-navy ring-1 ring-slate-200 hover:bg-slate-50',
        'navy' => 'bg-mk-navy text-white hover:bg-mk-blue',
        'ghost' => 'text-mk-navy hover:bg-slate-100',
    ];
    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
