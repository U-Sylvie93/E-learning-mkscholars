@php
    $academyCount = count($academies ?? []);
    $courseCount = count($courses ?? []);
    $studentCountValue = max((int) ($studentCount ?? 0), 0);
    $supportCount = 4;

    $marketingImages = [
        'hero' => asset('images/marketing/hero-learning.webp'),
        'academy' => asset('images/marketing/academy-learning.webp'),
        'skills' => asset('images/marketing/practical-courses.webp'),
        'support' => asset('images/marketing/student-support.webp'),
    ];
@endphp

<x-layouts.app title="Home">
    <section class="relative overflow-hidden bg-mk-navy text-white" data-testid="home-hero">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_18%,rgba(255,196,12,0.26),transparent_30%),linear-gradient(135deg,#073653_0%,#0e4a72_48%,#102a3a_100%)]"></div>
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-mk-cloud to-transparent"></div>

        <div class="mk-container relative grid min-h-[calc(100vh-5rem)] items-center gap-12 py-16 lg:grid-cols-[0.92fr_1.08fr] lg:py-24">
            <div>
                <div class="flex flex-wrap gap-2">
                    <x-badge tone="gold">Learning support</x-badge>
                    <x-badge tone="blue">Live Classes</x-badge>
                    <x-badge tone="green">Verified Certificates</x-badge>
                </div>
                <h1 class="mt-7 max-w-5xl text-4xl font-black tracking-normal text-white sm:text-5xl lg:text-6xl">
                    Learn with structure. Grow with support. Show your progress.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">
                    MK Scholars brings academy-based courses, practical assignments, live classes, learning support, and certificate-ready progress into one focused learning experience.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <x-button :href="route('courses')" size="lg">Explore Courses</x-button>
                    <x-button :href="route('about')" variant="secondary" size="lg">About MK Scholars</x-button>
                </div>
            </div>

            <div class="relative" data-testid="home-real-image-visual">
                <div class="absolute -left-5 top-8 h-28 w-28 rounded-full bg-mk-gold/25 blur-2xl"></div>
                <div class="absolute -right-5 bottom-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
                <div class="relative overflow-hidden rounded-[2.5rem] border border-white/15 bg-white/10 p-3 shadow-soft backdrop-blur">
                    <div class="relative overflow-hidden rounded-[2rem] bg-slate-900">
                        <img src="{{ $marketingImages['hero'] }}" alt="Students learning with MK Scholars support" class="h-[460px] w-full object-cover sm:h-[560px]">
                        <div class="absolute inset-0 bg-gradient-to-t from-mk-navy/82 via-mk-navy/18 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-6 sm:p-8">
                            <div class="max-w-md rounded-[1.5rem] border border-white/15 bg-white/92 p-5 text-mk-navy shadow-soft backdrop-blur">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-mk-goldSoft text-mk-navy ring-1 ring-mk-gold/30">
                                        <x-public-icon name="headset" class="h-5 w-5" />
                                    </span>
                                    <p class="text-xs font-extrabold uppercase tracking-wide text-mk-gold">Real learning support</p>
                                </div>
                                <h2 class="mt-3 text-2xl font-black tracking-normal">Courses, coaching, and progress students can understand.</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">A professional student journey from academy choice to verified achievement.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="relative z-10 -mt-8 bg-mk-cloud pb-16">
        <div class="mk-container">
            <div class="overflow-hidden rounded-[2rem] bg-[#0B5D8E] p-3 shadow-soft" data-testid="home-stats-belt">
                <div class="grid gap-3 md:grid-cols-4">
                    @foreach ([
                        [$academyCount, 'Academies', 'Focused learning lanes', 'academy'],
                        [$courseCount, 'Courses', 'Published learning paths', 'book'],
                        [$studentCountValue, 'Students supported', 'Registered student accounts', 'users'],
                        [$supportCount, 'Support pillars', 'Courses, learning support, live classes, certificates', 'headset'],
                    ] as $stat)
                        <div class="rounded-[1.5rem] bg-white/95 p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-soft md:p-7">
                            <div class="flex items-start gap-4">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#FFF6DC] text-[#062B45] ring-1 ring-mk-gold/40">
                                    <x-public-icon :name="$stat[3]" class="h-6 w-6" />
                                </span>
                                <div>
                                    <p class="text-4xl font-black text-[#062B45] sm:text-5xl">
                                        <span data-testid="animated-counter" data-counter-target="{{ $stat[0] }}">{{ $stat[0] }}</span>
                                    </p>
                                    <p class="mt-3 text-sm font-extrabold uppercase tracking-wide text-[#0B5D8E]">{{ $stat[1] }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $stat[2] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="py-20" data-testid="home-premium-image-cards">
        <div class="mk-container">
            <div class="grid gap-10 lg:grid-cols-[0.82fr_1.18fr] lg:items-end">
                <x-section-header eyebrow="The MK Scholars experience" title="A premium learning journey built around real student needs" description="The platform is designed to make learning feel guided, practical, and supported from the first course to verified progress." />
                <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
                    <x-button :href="route('academies')">View Academies</x-button>
                    <x-button :href="route('courses')" variant="secondary">Browse Courses</x-button>
                </div>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                @foreach ([
                    ['Academy-based learning', 'Choose a focused lane and follow a clear path through courses and milestones.', $marketingImages['academy'], 'lg:row-span-2', 'academy'],
                    ['Practical courses', 'Build useful skills through lessons, activities, quizzes, and assignments.', $marketingImages['skills'], '', 'play'],
                    ['Learning support', 'Keep momentum with check-ins, feedback, live sessions, and progress visibility.', $marketingImages['support'], '', 'message'],
                ] as $card)
                    <article class="group relative min-h-[330px] overflow-hidden rounded-[2rem] bg-mk-navy shadow-soft {{ $card[3] }}">
                        <img src="{{ $card[2] }}" alt="{{ $card[0] }}" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-mk-navy/90 via-mk-navy/35 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-7">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-mk-gold text-mk-navy shadow-sm shadow-mk-gold/20">
                                <x-public-icon :name="$card[4]" class="h-6 w-6" />
                            </span>
                            <h3 class="mt-5 text-3xl font-black tracking-normal text-white">{{ $card[0] }}</h3>
                            <p class="mt-3 max-w-xl text-sm leading-7 text-slate-200">{{ $card[1] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20">
        <div class="mk-container">
            <x-section-header eyebrow="Learning journey" title="Four steps that keep students moving" description="A guided journey from choosing a focus to showing real progress." />
            <div class="mt-12 grid gap-5 lg:grid-cols-4" data-testid="pathways-section">
                @foreach ([
                    ['Build tech skills', 'Start with practical tools, coding habits, and project confidence.', 'code'],
                    ['Prepare for exams', 'Use structured lessons and checkpoints to revise with less guesswork.', 'clipboard'],
                    ['Plan next steps', 'Organize certificates, documents, goals, and learning evidence.', 'compass'],
                    ['Grow career confidence', 'Practice communication, interviews, and portfolio-ready proof.', 'trending'],
                ] as $pathway)
                    <article class="relative rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6 shadow-sm transition hover:-translate-y-1 hover:bg-white hover:shadow-soft">
                        <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-mk-navy text-mk-gold shadow-sm">
                            <x-public-icon :name="$pathway[2]" class="h-7 w-7" />
                        </div>
                        <h3 class="mt-8 text-xl font-black tracking-normal text-mk-navy">{{ $pathway[0] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $pathway[1] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20">
        <div class="mk-container">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <x-section-header eyebrow="Academies" title="Focused academies for serious growth" description="Each academy gives students a clear learning lane with matching courses and support." />
                <x-button :href="route('academies')" variant="secondary">View Academies</x-button>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach (array_slice($academies, 0, 3) as $academy)
                    <x-academy-card :academy="$academy" />
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20">
        <div class="mk-container">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <x-section-header eyebrow="Courses" title="Popular courses with practical outcomes" description="Course cards help students scan level, duration, certificate value, and learning focus." />
                <x-button :href="route('courses')" variant="secondary">Explore Courses</x-button>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach (array_slice($courses, 0, 3) as $course)
                    <x-course-card :course="$course" />
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#EAF2F8] py-20">
        <div class="mk-container grid gap-8 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-[#0B5D8E]">Support system</p>
                <h2 class="mt-3 text-3xl font-extrabold text-mk-navy sm:text-4xl">Certificates, learning support, and live learning in one student workspace</h2>
                <p class="mt-5 text-sm leading-7 text-slate-600">MK Scholars keeps learning evidence visible through progress, quiz results, assignments, live class attendance, support check-ins, and public certificate verification.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['Verified certificates', 'certificate'],
                    ['Weekly check-ins', 'headset'],
                    ['Live class schedule', 'calendar'],
                    ['Progress tracker', 'chart'],
                ] as $item)
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-5 text-sm font-bold text-mk-navy shadow-sm transition hover:-translate-y-1 hover:shadow-soft">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-mk-gold text-mk-navy">
                            <x-public-icon :name="$item[1]" class="h-5 w-5" />
                        </span>
                        <span>{{ $item[0] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.app>




