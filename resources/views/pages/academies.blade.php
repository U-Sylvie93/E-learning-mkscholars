@php
    $academiesHeroImage = asset('images/marketing/academy-learning.webp');
    $academyCount = count($academies ?? []);
@endphp

<x-layouts.app title="Academies">
    <section class="relative overflow-hidden bg-mk-navy py-20 text-white sm:py-24" data-testid="academies-hero">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_18%,rgba(255,196,12,0.28),transparent_28%),linear-gradient(135deg,#073653_0%,#0e4a72_54%,#102a3a_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-mk-cloud to-transparent"></div>

        <div class="mk-container relative grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <div>
                <x-badge tone="gold">Academies</x-badge>
                <h1 class="mt-6 max-w-4xl text-4xl font-black tracking-normal text-white sm:text-5xl lg:text-6xl">Choose your academy pathway</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">Explore structured learning areas designed to help students build practical skills, prepare for exams, and grow with guided support.</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="#academy-list" class="inline-flex items-center justify-center gap-2 rounded-md bg-mk-gold px-6 py-3.5 text-sm font-extrabold text-mk-navy transition hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-mk-gold/50">
                        <x-public-icon name="academy" class="h-4 w-4" />
                        Browse Academies
                    </a>
                    <a href="{{ route('courses') }}" class="inline-flex items-center justify-center gap-2 rounded-md border border-white/20 px-6 py-3.5 text-sm font-extrabold text-white transition hover:border-mk-gold hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50">
                        <x-public-icon name="book" class="h-4 w-4" />
                        View Courses
                    </a>
                </div>
            </div>

            <div class="relative" data-testid="academies-visual-panel">
                <div class="absolute -left-6 top-8 h-32 w-32 rounded-full bg-mk-gold/25 blur-2xl"></div>
                <div class="absolute -right-8 bottom-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="relative overflow-hidden rounded-[2.25rem] border border-white/15 bg-white/10 p-3 shadow-soft backdrop-blur">
                    <img src="{{ $academiesHeroImage }}" alt="Students choosing an MK Scholars academy pathway" class="h-[440px] w-full rounded-[1.75rem] object-cover">
                    <div class="absolute inset-3 rounded-[1.75rem] bg-gradient-to-t from-mk-navy/82 via-mk-navy/20 to-transparent"></div>
                    <div class="absolute bottom-8 left-8 right-8 grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            ['Focused lanes', 'Choose one direction', 'compass'],
                            ['Guided support', 'Support team and live classes', 'headset'],
                        ] as $badge)
                            <div class="rounded-2xl border border-white/15 bg-white/92 p-4 text-mk-navy shadow-soft backdrop-blur">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-mk-goldSoft text-mk-navy ring-1 ring-mk-gold/30"><x-public-icon :name="$badge[2]" class="h-5 w-5" /></span>
                                <p class="mt-3 text-sm font-black">{{ $badge[0] }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-600">{{ $badge[1] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative -mt-10 pb-12">
        <div class="mk-container grid gap-4 md:grid-cols-4" data-testid="academies-trust-strip">
            @foreach ([
                [$academyCount, 'Academies', 'Structured learning pathways', 'academy'],
                ['5', 'Focus areas', 'Skills, exams, language, careers, study abroad', 'compass'],
                ['Live', 'Support', 'Learning support and class touchpoints', 'headset'],
                ['Proof', 'Progress', 'Certificates and visible milestones', 'certificate'],
            ] as $stat)
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-soft">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-mk-navy text-mk-gold"><x-public-icon :name="$stat[3]" class="h-5 w-5" /></span>
                        <div>
                            <p class="text-2xl font-black text-mk-navy">{{ $stat[0] }}</p>
                            <p class="mt-1 text-xs font-extrabold uppercase tracking-wide text-mk-gold">{{ $stat[1] }}</p>
                            <p class="mt-2 text-xs leading-5 text-slate-600">{{ $stat[2] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section id="academy-list" class="bg-mk-cloud pb-20 pt-4">
        <div class="mk-container">
            <div class="mb-8 grid gap-6 lg:grid-cols-[0.82fr_1.18fr] lg:items-end">
                <x-section-header eyebrow="Browse pathways" title="Pick the learning lane that matches your next goal" description="Every academy card is designed to help students scan the focus, support model, and matching courses before choosing where to begin." />
                <div class="grid gap-3 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
                    @foreach ([['Skills', 'code'], ['Exams', 'clipboard'], ['Progress', 'chart']] as $filter)
                        <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 text-sm font-extrabold text-mk-navy">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-mk-goldSoft text-mk-navy"><x-public-icon :name="$filter[1]" class="h-4 w-4" /></span>
                            {{ $filter[0] }}
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" data-testid="academies-grid">
                @forelse ($academies as $academy)
                    <x-academy-card :academy="$academy" />
                @empty
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-soft md:col-span-2 lg:col-span-3">
                        <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-mk-navy text-mk-gold"><x-public-icon name="academy" class="h-7 w-7" /></span>
                        <h2 class="mt-5 text-2xl font-black text-mk-navy">Academies are being prepared</h2>
                        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-600">Published academy pathways will appear here once they are available.</p>
                        <x-button :href="route('contact')" class="mt-6">Contact MK Scholars</x-button>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.app>

