@props(['tone' => 'gold'])

@php
    $tones = [
        'gold' => 'bg-mk-goldSoft text-mk-navy ring-mk-gold/30',
        'navy' => 'bg-mk-navy text-white ring-mk-navy/20',
        'blue' => 'bg-sky-50 text-sky-800 ring-sky-100',
        'green' => 'bg-emerald-50 text-emerald-800 ring-emerald-100',
        'gray' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 '.($tones[$tone] ?? $tones['gold'])]) }}>
    {{ $slot }}
</span>
