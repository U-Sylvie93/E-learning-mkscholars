@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'left',
])

<div {{ $attributes->merge(['class' => $align === 'center' ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl']) }}>
    @if ($eyebrow)
        <p class="text-sm font-bold uppercase tracking-wide text-mk-gold">{{ $eyebrow }}</p>
    @endif
    <h2 class="mt-3 text-3xl font-bold tracking-normal text-mk-navy sm:text-4xl">{{ $title }}</h2>
    @if ($description)
        <p class="mt-4 text-base leading-7 text-slate-600">{{ $description }}</p>
    @endif
</div>
