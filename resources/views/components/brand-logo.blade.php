@props([
    'showText' => true,
    'size' => 'md',
    'textClass' => 'text-mk-navy',
    'taglineClass' => 'text-slate-500',
])

@php
    $imageSize = [
        'sm' => 'h-9 w-9',
        'md' => 'h-11 w-11',
        'lg' => 'h-14 w-14',
    ][$size] ?? 'h-11 w-11';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <img
        src="{{ asset('images/mk-scholars-logo.webp') }}"
        alt="MK Scholars logo"
        class="{{ $imageSize }} shrink-0 rounded-full object-contain ring-1 ring-mk-gold/30"
        loading="eager"
    >

    @if ($showText)
        <span class="leading-tight">
            <span class="block text-base font-extrabold {{ $textClass }}">MK Scholars</span>
            <span class="block text-xs font-medium {{ $taglineClass }}">Unlocking Your Potential</span>
        </span>
    @endif
</span>
