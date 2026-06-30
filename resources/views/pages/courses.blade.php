@php
    $coursesHeroImage = asset('images/marketing/courses-hero.webp');
    $courseCount = count($courses ?? []);
@endphp

<x-layouts.app title="Courses">
    <section class="relative overflow-hidden bg-mk-navy py-20 text-white sm:py-24" data-testid="courses-hero">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_12%_18%,rgba(255,196,12,0.28),transparent_28%),linear-gradient(135deg,#073653_0%,#0e4a72_55%,#102a3a_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-mk-cloud to-transparent"></div>
        <div class="mk-container relative grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <div>
                <x-badge tone="gold">Course catalog</x-badge>
                <h1 class="mt-6 max-w-4xl text-4xl font-black tracking-normal text-white sm:text-5xl lg:text-6xl">Explore practical courses built for progress</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">Learn step by step with guided lessons, quizzes, assignments, live support, and verified completion.</p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="#course-list" class="inline-flex items-center justify-center gap-2 rounded-md bg-mk-gold px-6 py-3.5 text-sm font-extrabold text-mk-navy transition hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-mk-gold/50">
                        <x-public-icon name="book" class="h-4 w-4" />
                        Browse Courses
                    </a>
                    <a href="{{ route('academies') }}" class="inline-flex items-center justify-center gap-2 rounded-md border border-white/20 px-6 py-3.5 text-sm font-extrabold text-white transition hover:border-mk-gold hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50">
                        <x-public-icon name="academy" class="h-4 w-4" />
                        View Academies
                    </a>
                </div>
            </div>
            <div class="relative overflow-hidden rounded-[2.25rem] border border-white/15 bg-white/10 p-3 shadow-soft" data-testid="courses-visual-panel">
                <img src="{{ $coursesHeroImage }}" alt="Students building practical course skills" class="h-[460px] w-full rounded-[1.75rem] object-cover">
                <div class="absolute inset-3 rounded-[1.75rem] bg-gradient-to-t from-mk-navy/82 via-mk-navy/18 to-transparent"></div>
                <div class="absolute bottom-8 left-8 right-8 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-[1.5rem] bg-white/92 p-5 text-mk-navy shadow-soft backdrop-blur">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-mk-goldSoft text-mk-navy ring-1 ring-mk-gold/30"><x-public-icon name="play" class="h-5 w-5" /></span>
                        <p class="mt-3 text-xs font-extrabold uppercase tracking-wide text-mk-gold">Practical learning</p>
                        <h2 class="mt-2 text-xl font-black">Lessons, practice, feedback, and proof.</h2>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/15 bg-mk-navy/82 p-5 text-white shadow-soft backdrop-blur">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-mk-gold text-mk-navy"><x-public-icon name="chart" class="h-5 w-5" /></span>
                        <p class="mt-3 text-xs font-extrabold uppercase tracking-wide text-mk-gold">Progress visible</p>
                        <p class="mt-2 text-sm leading-6 text-slate-200">Follow lessons, activities, quizzes, and certificate readiness.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative -mt-10 pb-12">
        <div class="mk-container grid gap-4 md:grid-cols-4" data-testid="courses-trust-strip">
            @foreach ([
                [$courseCount, 'Courses', 'Published learning paths ready to explore', 'book'],
                ['Cert', 'Verified proof', 'Certificate-ready progress after completion', 'certificate'],
                ['Live', 'Class support', 'External live sessions and learning guidance', 'headset'],
                ['Tasks', 'Practice', 'Quizzes, assignments, and activities', 'clipboard'],
            ] as $benefit)
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-soft">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-mk-navy text-mk-gold shadow-sm">
                            <x-public-icon :name="$benefit[3]" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-2xl font-black text-mk-navy">{{ $benefit[0] }}</p>
                            <p class="mt-1 text-xs font-extrabold uppercase tracking-wide text-mk-gold">{{ $benefit[1] }}</p>
                            <p class="mt-2 text-xs leading-5 text-slate-600">{{ $benefit[2] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section id="course-list" class="bg-mk-cloud pb-20 pt-4">
        <div class="mk-container">
            @if ($activeAcademy)
                <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-mk-gold/30 bg-mk-goldSoft/70 p-4 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm font-bold text-mk-navy">Showing courses for selected academy.</p>
                    <x-button :href="route('courses')" size="sm" variant="secondary">Clear Filter</x-button>
                </div>
            @endif

            <div class="mb-8 grid gap-6 lg:grid-cols-[0.82fr_1.18fr] lg:items-end">
                <x-section-header eyebrow="Browse courses" title="Choose a practical course and start with clarity" description="Scan the academy, level, lesson count, certificate value, and pricing before opening a full course profile." />
                <div class="grid gap-3 rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
                    @foreach ([['Learning path', 'compass'], ['Assignments', 'clipboard'], ['Progress', 'chart']] as $filter)
                        <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 text-sm font-extrabold text-mk-navy">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-mk-goldSoft text-mk-navy"><x-public-icon :name="$filter[1]" class="h-4 w-4" /></span>
                            {{ $filter[0] }}
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" data-testid="courses-grid">
                @forelse ($courses as $course)
                    <x-course-card :course="$course" />
                @empty
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-soft md:col-span-2 lg:col-span-3">
                        <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-mk-navy text-mk-gold"><x-public-icon name="book" class="h-7 w-7" /></span>
                        <h2 class="mt-5 text-2xl font-black text-mk-navy">Courses are being prepared</h2>
                        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-600">Published courses will appear here once available.</p>
                        <x-button :href="route('contact')" class="mt-6">Contact MK Scholars</x-button>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.app>

