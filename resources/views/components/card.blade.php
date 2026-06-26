@props(['highlighted' => false])

<div {{ $attributes->merge([
    'class' => $highlighted
        ? 'rounded-lg border border-mk-gold/60 bg-white p-6 shadow-soft'
        : 'rounded-lg border border-slate-200 bg-white p-6 shadow-sm'
]) }}>
    {{ $slot }}
</div>
