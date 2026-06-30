@php
    $marketingImages = [
        'about' => asset('images/marketing/about-learning.webp'),
        'academy' => asset('images/marketing/academy-learning.webp'),
        'skills' => asset('images/marketing/practical-courses.webp'),
        'support' => asset('images/marketing/student-support.webp'),
    ];
@endphp

<x-layouts.app title="About">
    <section class="relative overflow-hidden bg-mk-navy py-20 text-white sm:py-24">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_18%,rgba(255,196,12,0.26),transparent_28%),linear-gradient(135deg,#073653_0%,#0e4a72_58%,#102a3a_100%)]"></div>
        <div class="mk-container relative grid gap-12 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
            <div>
                <x-badge tone="gold">About MK Scholars</x-badge>
                <h1 class="mt-6 max-w-4xl text-4xl font-black tracking-normal text-white sm:text-5xl lg:text-6xl">Built for disciplined, hopeful, student-centered learning.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">MK Scholars is an e-learning platform where academies, practical courses, learning guidance, live classes, assignments, progress tracking, and certificates work together.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-button :href="route('courses')" size="lg">Browse Courses</x-button>
                    <x-button :href="route('contact')" variant="secondary" size="lg">Talk to Us</x-button>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2" data-testid="about-story-section">
                <div class="relative min-h-[430px] overflow-hidden rounded-[2rem] sm:col-span-2">
                    <img src="{{ $marketingImages['about'] }}" alt="MK Scholars students learning together" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-mk-navy/82 via-mk-navy/18 to-transparent"></div>
                    <div class="absolute bottom-0 p-7">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-mk-gold text-mk-navy shadow-sm shadow-mk-gold/20">
                            <x-public-icon name="users" class="h-6 w-6" />
                        </span>
                        <p class="mt-4 text-sm font-extrabold uppercase tracking-wide text-mk-gold">Who we are</p>
                        <h2 class="mt-3 text-3xl font-black tracking-normal text-white">A modern learning home for ambitious students.</h2>
                    </div>
                </div>
                <div class="rounded-[1.5rem] border border-white/10 bg-white/10 p-6 backdrop-blur">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-mk-gold text-mk-navy"><x-public-icon name="compass" class="h-5 w-5" /></span>
                    <p class="mt-4 text-sm font-extrabold uppercase tracking-wide text-mk-gold">Mission</p>
                    <p class="mt-3 text-sm leading-7 text-slate-200">Make structured learning easier to follow through clear paths and visible progress.</p>
                </div>
                <div class="rounded-[1.5rem] border border-white/10 bg-white/10 p-6 backdrop-blur">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-mk-gold text-mk-navy"><x-public-icon name="trending" class="h-5 w-5" /></span>
                    <p class="mt-4 text-sm font-extrabold uppercase tracking-wide text-mk-gold">Vision</p>
                    <p class="mt-3 text-sm leading-7 text-slate-200">Support students from learning to opportunity with practical evidence and coaching.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-[#EAF2F8] py-20">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.82fr_1.18fr] lg:items-start">
            <x-section-header eyebrow="What MK Scholars does" title="One platform for the complete learning cycle" description="The current system supports the core workflows students and administrators need to keep learning organized and visible." />
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ([
                    ['Academy-based learning', 'Focused lanes help students choose a direction and stay organized.', 'academy'],
                    ['Practical skills and courses', 'Lessons, modules, quizzes, assignments, and activities help learners practice.', 'book'],
                    ['Learning support', 'Check-ins, live sessions, and feedback keep students from learning alone.', 'headset'],
                    ['Certificates and verified progress', 'Completion rules and public verification make achievement easier to share.', 'certificate'],
                ] as $block)
                    <article class="rounded-[1.75rem] border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-soft">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mk-navy text-mk-gold shadow-sm">
                            <x-public-icon :name="$block[2]" class="h-6 w-6" />
                        </span>
                        <h2 class="mt-6 text-xl font-black text-mk-navy">{{ $block[0] }}</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $block[1] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20">
        <div class="mk-container grid gap-8 lg:grid-cols-3">
            @foreach ([
                ['How students learn', 'Students follow modules, lessons, lesson activities, quizzes, assignments, live classes, and progress checkpoints.', $marketingImages['skills'], 'play'],
                ['Student learning support', 'Support assignments and check-ins give students a visible support structure instead of silent self-study.', $marketingImages['support'], 'message'],
                ['Our learning promise', 'We keep the experience focused, organized, mobile-friendly, and honest about what the platform already supports.', $marketingImages['academy'], 'sparkles'],
            ] as $story)
                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-soft">
                    <div class="relative">
                        <img src="{{ $story[2] }}" alt="{{ $story[0] }}" class="h-64 w-full object-cover">
                        <span class="absolute bottom-4 left-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-mk-gold text-mk-navy shadow-soft">
                            <x-public-icon :name="$story[3]" class="h-6 w-6" />
                        </span>
                    </div>
                    <div class="p-7">
                        <h2 class="text-2xl font-black text-mk-navy">{{ $story[0] }}</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $story[1] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="bg-[#FFF6DC] py-20 text-mk-navy">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
            <div>
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-mk-gold text-mk-navy"><x-public-icon name="certificate" class="h-6 w-6" /></span>
                <p class="mt-5 text-sm font-extrabold uppercase tracking-wide text-[#0B5D8E]">Learning promise</p>
                <h2 class="mt-4 text-3xl font-black tracking-normal text-mk-navy sm:text-4xl">Clear paths, practical work, supportive feedback, and proof students can share.</h2>
            </div>
            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-soft">
                <p class="text-sm leading-7 text-slate-600">MK Scholars is built around the simple idea that students make better progress when their learning path, student support, live class schedule, assignments, payment access, and certificate eligibility are visible in one place.</p>
                <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                    <x-button :href="route('courses')">Explore Courses</x-button>
                    <x-button :href="route('contact')" variant="secondary">Contact MK Scholars</x-button>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>





