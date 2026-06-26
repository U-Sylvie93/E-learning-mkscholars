@php
    $image = $course['image'] ?? 'https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=1200&q=85';
    $lessonsCount = $course['lessons_count'] ?? collect($course['modules'] ?? [])->sum(fn ($module) => count($module['lessons'] ?? []));
    $skills = array_slice($course['outcomes'] ?? ['Focused study habits', 'Portfolio-ready practice', 'Confident communication'], 0, 4);
    $ctaState = $course['cta_state'] ?? 'guest';
    $academyIcon = $course['academy_icon'] ?? \App\Models\Academy::ICON_BOOK_OPEN;
@endphp

<x-layouts.app :title="$course['title']">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
            <div>
                <div class="flex flex-wrap gap-2">
                    <x-badge tone="blue">{{ $course['level'] }}</x-badge>
                    <x-badge tone="gray">{{ $course['duration'] }}</x-badge>
                    <x-badge tone="green">Certificate</x-badge>
                    @if ($lessonsCount)
                        <x-badge tone="gray">{{ $lessonsCount }} lessons</x-badge>
                    @endif
                </div>
                <h1 class="mt-6 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">{{ $course['title'] }}</h1>
                <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-mk-goldSoft px-4 py-2 text-sm font-bold text-mk-navy">
                    <x-academy-icon :name="$academyIcon" class="h-4 w-4 text-mk-gold" />
                    {{ $course['academy'] }}
                </div>
                <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600">{{ $course['short_description'] ?? $course['summary'] }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    @if ($ctaState === 'student_not_enrolled' && ! empty($course['id']))
                        <form method="POST" action="{{ route('courses.enroll', $course['id']) }}">
                            @csrf
                            <x-button type="submit" size="lg">Enroll Free</x-button>
                        </form>
                    @elseif ($ctaState === 'paid_not_started' && ! empty($course['id']))
                        <form method="POST" action="{{ route('courses.enroll', $course['id']) }}">
                            @csrf
                            <x-button type="submit" size="lg">Pay & Enroll</x-button>
                        </form>
                    @elseif ($ctaState === 'payment_pending' && ! empty($course['payment_id']))
                        <x-button :href="route('student.payments.show', $course['payment_id'])" size="lg">Payment Pending</x-button>
                    @elseif ($ctaState === 'payment_rejected' && ! empty($course['payment_id']))
                        <x-button :href="route('student.payments.show', $course['payment_id'])" size="lg">Resubmit Payment</x-button>
                    @elseif ($ctaState === 'enrolled' && ! empty($course['id']))
                        <x-button :href="route('student.courses.learn', $course['id'])" size="lg">Continue Learning</x-button>
                    @elseif ($ctaState === 'non_student')
                        <x-button :href="route('courses.show', $course['slug'])" size="lg">View Course</x-button>
                    @else
                        <x-button :href="route('login')" size="lg">Login to Continue</x-button>
                    @endif
                    <x-button :href="route('courses')" variant="secondary" size="lg">Back to Courses</x-button>
                </div>
            </div>

            <div>
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-100 shadow-soft">
                    <img class="h-80 w-full object-cover sm:h-[440px]" src="{{ $image }}" alt="{{ $course['title'] }}">
                </div>
                <x-card class="-mt-8 ml-4 mr-4 relative">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Price</dt>
                            <dd class="mt-1 font-extrabold text-mk-navy">{{ $course['price'] }}</dd>
                            <dd class="mt-2"><x-badge :tone="($course['is_free'] ?? true) ? 'green' : 'gold'">{{ ($course['is_free'] ?? true) ? 'Free access' : 'Manual payment' }}</x-badge></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Academy</dt>
                            <dd class="mt-1 inline-flex items-center gap-2 font-extrabold text-mk-navy"><x-academy-icon :name="$academyIcon" class="h-4 w-4 text-mk-gold" /> {{ $course['academy'] }}</dd>
                        </div>
                    </dl>
                </x-card>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-[0.72fr_1.28fr] lg:items-start">
            <div class="space-y-6">
                <x-section-header eyebrow="Overview" title="Course overview" description="A guided course experience with clear lessons, practice checkpoints, and outcomes students can explain." />
                <x-card>
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                        <img class="h-52 w-full object-cover" src="{{ $image }}" alt="{{ $course['title'] }} course overview image">
                    </div>
                    <dl class="mt-5 grid gap-4 text-sm">
                        <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-3">
                            <dt class="font-bold text-slate-500">Level</dt>
                            <dd class="font-extrabold text-mk-navy">{{ $course['level'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-3">
                            <dt class="font-bold text-slate-500">Duration</dt>
                            <dd class="font-extrabold text-mk-navy">{{ $course['duration'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="font-bold text-slate-500">Lessons</dt>
                            <dd class="font-extrabold text-mk-navy">{{ $lessonsCount ?: 'Coming soon' }}</dd>
                        </div>
                    </dl>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card>
                    @if (! empty($course['rendered_full_description']))
                        <div class="mk-rich-content">{!! $course['rendered_full_description'] !!}</div>
                    @elseif (! empty($course['summary']))
                        <p class="text-sm leading-7 text-slate-600">{{ $course['summary'] }}</p>
                    @else
                        <p class="text-sm leading-7 text-slate-600">The full course overview will be published soon.</p>
                    @endif
                </x-card>

                <x-card>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-wide text-mk-gold">Outcomes</p>
                            <h2 class="mt-1 text-2xl font-extrabold text-mk-navy">What you will learn</h2>
                        </div>
                        <x-badge tone="blue">Student focused</x-badge>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        @forelse (($course['outcomes'] ?? []) as $outcome)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 font-bold text-mk-navy">{{ $outcome }}</div>
                        @empty
                            <p class="text-sm leading-6 text-slate-600">Learning outcomes will be published soon.</p>
                        @endforelse
                    </div>
                </x-card>
            </div>
        </div>
    </section>

    <section class="bg-white py-16">
        <div class="mk-container">
            <x-section-header eyebrow="Skills" title="Skills you will gain" />
            <div class="mt-8 flex flex-wrap gap-3">
                @foreach ($skills as $skill)
                    <x-badge tone="gold">{{ $skill }}</x-badge>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            <x-section-header eyebrow="Syllabus" title="Course syllabus" description="Preview the published modules, lessons, lesson types, and free preview items." />
            <div class="mt-10 grid gap-5">
                @forelse (($course['modules'] ?? []) as $module)
                    <x-card>
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-mk-navy">{{ $module['title'] }}</h3>
                                @if (! empty($module['summary']))
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $module['summary'] }}</p>
                                @endif
                            </div>
                            <x-badge tone="gray">{{ count($module['lessons']) }} lessons</x-badge>
                        </div>
                        <div class="mt-6 divide-y divide-slate-100 rounded-lg border border-slate-100">
                            @forelse ($module['lessons'] as $lesson)
                                <div class="flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="font-bold text-mk-navy">{{ $lesson['title'] }}</h4>
                                            @if ($lesson['is_free_preview'])
                                                <x-badge>Free preview</x-badge>
                                            @endif
                                        </div>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            <span>{{ $lesson['lesson_type'] }}</span>
                                            @if (! empty($lesson['duration_minutes']))
                                                <span>{{ $lesson['duration_minutes'] }} min</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-sm text-slate-600">Lessons will be published soon.</div>
                            @endforelse
                        </div>
                    </x-card>
                @empty
                    <x-card><p class="text-sm leading-6 text-slate-600">The curriculum outline will be published soon.</p></x-card>
                @endforelse
            </div>
        </div>
    </section>

    <section class="bg-white py-16">
        <div class="mk-container grid gap-5 md:grid-cols-3">
            @foreach ([['Quizzes', 'Check understanding with structured questions.'], ['Assignments', 'Build work students can review and improve.'], ['Live classes', 'Join scheduled sessions with external meeting links.']] as $preview)
                <x-card>
                    <x-badge tone="blue">{{ $preview[0] }}</x-badge>
                    <p class="mt-4 text-sm leading-6 text-slate-600">{{ $preview[1] }}</p>
                </x-card>
            @endforeach
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-2">
            <x-card>
                <x-section-header eyebrow="Support" title="Instructor and mentor support" />
                <p class="mt-6 text-sm leading-7 text-slate-600">Students can learn through lessons, submit work, attend live sessions, and receive mentor guidance as the course experience expands.</p>
            </x-card>
            <x-card>
                <x-section-header eyebrow="FAQ" title="Before you enroll" />
                <div class="mt-6 space-y-4">
                    <p class="text-sm leading-6 text-slate-600"><strong class="text-mk-navy">Will I get a certificate?</strong> Certificates can be issued and publicly verified after completion.</p>
                    <p class="text-sm leading-6 text-slate-600"><strong class="text-mk-navy">Can I preview lessons?</strong> Free preview lessons are marked inside the syllabus.</p>
                </div>
            </x-card>
        </div>
    </section>

    @if (($relatedOpportunities ?? collect())->isNotEmpty())
        <section class="bg-white py-16">
            <div class="mk-container">
                <x-section-header eyebrow="Opportunities" title="Related opportunities to explore" />
                <div class="mt-8 grid gap-5 md:grid-cols-3">
                    @foreach ($relatedOpportunities as $opportunity)
                        <x-opportunity-card :opportunity="$opportunity->toPublicCard()" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="bg-white py-16">
        <div class="mk-container">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <x-section-header
                    eyebrow="Student feedback"
                    title="Course reviews"
                    description="Published feedback from MK Scholars students who have joined this course."
                />
                <div class="rounded-lg border border-slate-100 bg-slate-50 px-5 py-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Average rating</p>
                    <p class="mt-1 text-2xl font-extrabold text-mk-navy">
                        {{ $course['average_rating'] ?? 'New' }}
                        @if (! empty($course['average_rating']))
                            <span class="text-sm font-bold text-slate-500">/ 5</span>
                        @endif
                    </p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $course['reviews_count'] ?? 0 }} published reviews</p>
                </div>
            </div>

            <div class="mt-8 grid gap-5 md:grid-cols-2">
                @forelse (($course['reviews'] ?? []) as $review)
                    <x-card>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-bold text-mk-navy">{{ $review['reviewer'] }}</p>
                                @if (! empty($review['created_at']))
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $review['created_at'] }}</p>
                                @endif
                            </div>
                            <x-badge tone="gold">{{ $review['rating'] }}/5</x-badge>
                        </div>
                        @if (! empty($review['comment']))
                            <p class="mt-4 text-sm leading-7 text-slate-600">{{ $review['comment'] }}</p>
                        @else
                            <p class="mt-4 text-sm leading-7 text-slate-600">This student left a rating without a written comment.</p>
                        @endif
                    </x-card>
                @empty
                    <x-card>
                        <x-badge tone="gray">No published reviews yet</x-badge>
                        <p class="mt-4 text-sm leading-6 text-slate-600">Student feedback will appear here after admin moderation.</p>
                    </x-card>
                @endforelse
            </div>
        </div>
    </section>

    <section class="bg-mk-navy py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-mk-gold">Ready to begin?</p>
                <h2 class="mt-3 text-3xl font-extrabold text-white">Start learning with MK Scholars</h2>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                @if ($ctaState === 'student_not_enrolled' && ! empty($course['id']))
                    <form method="POST" action="{{ route('courses.enroll', $course['id']) }}">
                        @csrf
                        <x-button type="submit" size="lg">Enroll Free</x-button>
                    </form>
                @elseif ($ctaState === 'paid_not_started' && ! empty($course['id']))
                    <form method="POST" action="{{ route('courses.enroll', $course['id']) }}">
                        @csrf
                        <x-button type="submit" size="lg">Pay & Enroll</x-button>
                    </form>
                @elseif ($ctaState === 'payment_pending' && ! empty($course['payment_id']))
                    <x-button :href="route('student.payments.show', $course['payment_id'])" size="lg">Payment Pending</x-button>
                @elseif ($ctaState === 'payment_rejected' && ! empty($course['payment_id']))
                    <x-button :href="route('student.payments.show', $course['payment_id'])" size="lg">Resubmit Payment</x-button>
                @elseif ($ctaState === 'enrolled' && ! empty($course['id']))
                    <x-button :href="route('student.courses.learn', $course['id'])" size="lg">Continue Learning</x-button>
                @elseif ($ctaState === 'non_student')
                    <x-button :href="route('courses.show', $course['slug'])" size="lg">View Course</x-button>
                @else
                    <x-button :href="route('login')" size="lg">Login to Continue</x-button>
                @endif
            </div>
        </div>
    </section>

    <section class="bg-white py-16">
        <div class="mk-container">
            <x-section-header eyebrow="Related" title="Continue exploring" />
            <div class="mt-8 grid gap-5 md:grid-cols-2">
                @foreach ($relatedCourses as $related)
                    <x-course-card :course="$related" />
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.app>

