<x-layouts.app title="Home">
    <section class="bg-white">
        <div class="mk-container grid min-h-[calc(100vh-4rem)] items-center gap-10 py-12 lg:grid-cols-[1fr_0.95fr] lg:py-16">
            <div>
                <x-brand-logo size="lg" class="mb-8" />
                <div class="flex flex-wrap gap-2">
                    <x-badge>Mentorship</x-badge>
                    <x-badge tone="blue">Live Classes</x-badge>
                    <x-badge tone="green">Verified Certificates</x-badge>

                </div>
                <h1 class="mt-6 max-w-5xl text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl lg:text-6xl">
                    Learn skills. Get coached. Earn certificates. Build your future.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                    MK Scholars combines structured academies, practical courses, mentor support, and live sessions for ambitious students.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-button :href="route('courses')" size="lg">Explore Courses</x-button>

                </div>
                <div class="mt-10 grid max-w-2xl grid-cols-3 gap-4">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-2xl font-extrabold text-mk-navy">5</p>
                        <p class="mt-1 text-xs font-semibold uppercase text-slate-500">Academies</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-2xl font-extrabold text-mk-navy">360</p>
                        <p class="mt-1 text-xs font-semibold uppercase text-slate-500">Support loop</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-2xl font-extrabold text-mk-navy">1</p>
                        <p class="mt-1 text-xs font-semibold uppercase text-slate-500">Student hub</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-100 shadow-soft">
                    <img class="h-[360px] w-full object-cover sm:h-[460px]" src="https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=1200&q=85" alt="Students studying on campus">
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-bold text-mk-navy">Course progress</p>
                        <div class="mt-3 h-2 rounded-full bg-slate-100"><div class="h-2 w-4/5 rounded-full bg-mk-gold"></div></div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-bold text-mk-navy">Application ready</p>
                        <p class="mt-2 text-xs font-semibold text-slate-500">CV, transcript, and letter organized</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-slate-200 bg-slate-50 py-8">
        <div class="mk-container grid gap-4 md:grid-cols-4">
            @foreach (['Skill pathways', 'Mentor feedback', 'Project practice', 'Certificate progress'] as $value)
                <div class="rounded-lg bg-white p-4 text-center text-sm font-bold text-mk-navy shadow-sm">{{ $value }}</div>
            @endforeach
        </div>
    </section>

    <section class="py-20">
        <div class="mk-container">
            <x-section-header eyebrow="Pathways" title="Start with your goal" description="Choose a guided direction, then combine courses, coaching, and application support." />
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                @foreach ([['Build tech skills', 'Coding, digital tools, and portfolio projects'], ['Prepare for exams', 'Focused practice and progress structure'], ['Plan next steps', 'Study goals, documents, and future readiness'], ['Grow career confidence', 'Interviews, CVs, and mentor feedback']] as $pathway)
                    <x-card>
                        <x-badge tone="gold">{{ $loop->iteration }}</x-badge>
                        <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $pathway[0] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $pathway[1] }}</p>
                    </x-card>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20">
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

    <section class="py-20">
        <div class="mk-container">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <x-section-header eyebrow="Courses" title="Popular courses with practical outcomes" description="Image-rich course cards help students scan level, duration, certificate value, and learning focus." />
                <x-button :href="route('courses')" variant="secondary">Explore Courses</x-button>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach (array_slice($courses, 0, 3) as $course)
                    <x-course-card :course="$course" />
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
            <x-section-header eyebrow="How it works" title="A clear path from learning to action" description="Students do not just watch lessons. They build evidence, get feedback, and prepare their next step with confidence." />
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ([['1', 'Choose an academy'], ['2', 'Learn through courses'], ['3', 'Get mentor feedback'], ['4', 'Organize your progress']] as $step)
                    <x-card>
                        <x-badge tone="navy">{{ $step[0] }}</x-badge>
                        <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $step[1] }}</h3>
                    </x-card>
                @endforeach
            </div>
        </div>
    </section>


    <section class="bg-mk-navy py-20">
        <div class="mk-container grid gap-8 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-mk-gold">Support system</p>
                <h2 class="mt-3 text-3xl font-extrabold text-white sm:text-4xl">Certificates, mentorship, and live learning in one student workspace</h2>
                <p class="mt-5 text-sm leading-7 text-slate-200">MK Scholars keeps learning evidence visible through progress, quiz results, assignments, live class attendance, mentor check-ins, and public certificate verification.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (['Verified certificates', 'Weekly check-ins', 'Live class schedule', 'Application tracker'] as $item)
                    <div class="rounded-lg border border-white/10 bg-white/10 p-5 text-sm font-bold text-white">{{ $item }}</div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white py-20">
        <div class="mk-container">
            <x-section-header eyebrow="FAQ" title="Questions students ask first" />
            <div class="mt-8 grid gap-5 md:grid-cols-2">
                @foreach ([['Do courses include certificates?', 'Course completion can lead to issued certificates with public verification.'], ['Are live classes included?', 'The platform supports scheduled external live classes and attendance tracking.'], ['Can I get mentor support?', 'Yes. MK Scholars supports mentor guidance, check-ins, and feedback during learning.'], ['Is this only for one subject?', 'No. MK Scholars supports academies across tech, language, test prep, careers, and study abroad.']] as $faq)
                    <x-card>
                        <h3 class="font-bold text-mk-navy">{{ $faq[0] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $faq[1] }}</p>
                    </x-card>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.app>


