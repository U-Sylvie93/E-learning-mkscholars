@php
    $contactHeroImage = asset('images/marketing/contact-support.webp');
@endphp

<x-layouts.app title="Contact">
    <section class="relative overflow-hidden bg-white py-20 sm:py-24">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.20),transparent_32%),linear-gradient(135deg,#ffffff_0%,#f3f8fb_60%,#ffffff_100%)]"></div>
        <div class="mk-container relative grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <div>
                <x-badge tone="gold">Contact</x-badge>
                <h1 class="mt-6 text-4xl font-black tracking-normal text-mk-navy sm:text-5xl lg:text-6xl">Start a learning conversation with MK Scholars.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">Ask about academies, courses, learning support, certificates, payments, or the best next step for a student. We will help you find a clear path.</p>
            </div>

            <div class="relative overflow-hidden rounded-[2.25rem] border border-slate-200 bg-mk-navy p-3 shadow-soft">
                <img src="{{ $contactHeroImage }}" alt="MK Scholars student support conversation" class="h-[430px] w-full rounded-[1.75rem] object-cover">
                <div class="absolute inset-3 rounded-[1.75rem] bg-gradient-to-t from-mk-navy/82 via-mk-navy/20 to-transparent"></div>
                <div class="absolute bottom-8 left-8 right-8 rounded-[1.5rem] border border-white/15 bg-white/92 p-5 text-mk-navy shadow-soft backdrop-blur">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-mk-goldSoft text-mk-navy ring-1 ring-mk-gold/30"><x-public-icon name="message" class="h-5 w-5" /></span>
                        <p class="text-xs font-extrabold uppercase tracking-wide text-mk-gold">Support promise</p>
                    </div>
                    <h2 class="mt-3 text-2xl font-black tracking-normal">Clear answers for students, parents, support team, and partners.</h2>
                </div>
            </div>
        </div>
    </section>

    <section class="relative -mt-10 pb-16">
        <div class="mk-container grid gap-5 md:grid-cols-3" data-testid="contact-trust-cards">
            @foreach ([
                ['Phone', '+250798611161', 'tel:+250798611161', 'phone'],
                ['Email', 'mkscholars250@gmail.com', 'mailto:mkscholars250@gmail.com', 'mail'],
                ['Location', 'Kigali, Rwanda - Kicukiro', null, 'pin'],
            ] as $card)
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-soft">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mk-goldSoft text-mk-navy ring-1 ring-mk-gold/30">
                        <x-public-icon :name="$card[3]" class="h-6 w-6" />
                    </div>
                    <p class="mt-5 text-sm font-extrabold uppercase tracking-wide text-mk-gold">{{ $card[0] }}</p>
                    @if ($card[2])
                        <a href="{{ $card[2] }}" class="mt-2 block break-words text-lg font-extrabold text-mk-navy transition hover:text-mk-blue focus:outline-none focus:ring-2 focus:ring-mk-gold/50">{{ $card[1] }}</a>
                    @else
                        <p class="mt-2 break-words text-lg font-extrabold text-mk-navy">{{ $card[1] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="pb-20">
        <div class="mk-container grid gap-8 lg:grid-cols-[0.75fr_1.25fr] lg:items-start">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-soft">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-mk-navy text-mk-gold"><x-public-icon name="headset" class="h-6 w-6" /></span>
                <p class="mt-5 text-sm font-extrabold uppercase tracking-wide text-mk-gold">How we can help</p>
                <h2 class="mt-4 text-2xl font-black tracking-normal text-mk-navy">Choose the right learning path with confidence.</h2>
                <div class="mt-6 space-y-4 text-sm leading-7 text-slate-600">
                    <p><span class="font-bold text-mk-navy">Support:</span> Monday to Friday</p>
                    <p><span class="font-bold text-mk-navy">Focus:</span> Academies, courses, learning support, certificates, and student progress.</p>
                    <p><span class="font-bold text-mk-navy">Best for:</span> Students, parents, support team, and school partners.</p>
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-2 shadow-soft">
                <livewire:contact-form />
            </div>
        </div>
    </section>
</x-layouts.app>

