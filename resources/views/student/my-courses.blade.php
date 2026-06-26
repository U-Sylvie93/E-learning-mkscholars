<x-dashboard-layout role="student" title="My Courses" description="MK Scholars enrolled courses.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Student"
                title="My Courses"
                description="Continue learning from the courses you have enrolled in."
            />
            <x-badge tone="gray">{{ $enrollments->count() }} enrolled courses</x-badge>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            @if ($enrollments->isEmpty())
                <x-card>
                    <h2 class="text-xl font-bold text-mk-navy">No courses yet</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Explore the course catalog and enroll when you are ready to begin.</p>
                    <x-button :href="route('courses')" class="mt-6">Browse Courses</x-button>
                </x-card>
            @else
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($enrollments as $item)
                        <x-card class="flex h-full flex-col">
                            <div class="flex items-center justify-between gap-3">
                                <x-badge tone="blue">{{ $item['course']->level }}</x-badge>
                                <span class="text-xs font-semibold text-slate-500">{{ $item['course']->duration }}</span>
                            </div>
                            <h2 class="mt-5 text-xl font-bold text-mk-navy">{{ $item['course']->title }}</h2>
                            <p class="mt-2 text-sm font-semibold text-mk-gold">{{ $item['course']->academy?->name ?? 'MK Scholars' }}</p>
                            <div class="mt-6">
                                <div class="flex items-center justify-between text-sm font-semibold">
                                    <span class="text-slate-600">Progress</span>
                                    <span class="text-mk-navy">{{ $item['progress'] }}%</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-mk-gold" style="width: {{ $item['progress'] }}%"></div>
                                </div>
                            </div>
                            <div class="mt-5 rounded-lg border border-slate-100 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge :tone="$item['completion']->is_eligible_for_certificate ? 'green' : 'gold'">
                                        {{ $item['completion']->is_eligible_for_certificate ? 'Certificate eligible' : 'In progress' }}
                                    </x-badge>
                                    <x-badge tone="gray">{{ $item['completion']->lesson_percentage }}% lessons</x-badge>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Completion:
                                    <span class="font-bold text-mk-navy">{{ $item['completion']->lesson_percentage }}% lessons</span>,
                                    <span class="font-bold text-mk-navy">{{ $item['completion']->quiz_percentage }}% quizzes</span>,
                                    <span class="font-bold text-mk-navy">{{ $item['completion']->assignment_percentage }}% assignments</span>
                                </p>
                            </div>
                            <x-button :href="route('student.courses.learn', $item['course'])" class="mt-6 w-full">Continue Learning</x-button>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-dashboard-layout>

