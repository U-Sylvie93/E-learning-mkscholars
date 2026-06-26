<x-layouts.app title="Courses">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
            <div>
                <x-badge tone="gold">Course catalog</x-badge>
                <h1 class="mt-5 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">Courses built for measurable student progress</h1>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">Browse practical courses with images, certificate value, lesson structure, mentorship context, and opportunity-focused outcomes.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-6">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-3xl font-extrabold text-mk-navy">{{ count($courses) }}</p>
                        <p class="mt-1 text-xs font-bold uppercase tracking-wide text-slate-500">Courses</p>
                    </div>
                    <div>
                        <p class="text-3xl font-extrabold text-mk-navy">Cert</p>
                        <p class="mt-1 text-xs font-bold uppercase tracking-wide text-slate-500">Verified proof</p>
                    </div>
                    <div>
                        <p class="text-3xl font-extrabold text-mk-navy">Live</p>
                        <p class="mt-1 text-xs font-bold uppercase tracking-wide text-slate-500">Class support</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            @if ($activeAcademy)
                <div class="mb-6 flex flex-col gap-3 rounded-lg border border-mk-gold/30 bg-mk-goldSoft/40 p-4 md:flex-row md:items-center md:justify-between">
                    <p class="text-sm font-bold text-mk-navy">Showing courses for selected academy.</p>
                    <x-button :href="route('courses')" size="sm" variant="secondary">Clear Filter</x-button>
                </div>
            @endif

            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($courses as $course)
                    <x-course-card :course="$course" />
                @empty
                    <x-card class="md:col-span-2 lg:col-span-3">
                        <x-badge tone="gray">Coming soon</x-badge>
                        <h2 class="mt-5 text-xl font-bold text-mk-navy">Courses are being prepared</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Published courses will appear here once available.</p>
                    </x-card>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.app>
