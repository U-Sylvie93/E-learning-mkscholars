<x-dashboard-layout role="instructor" :title="$course->title" description="MK Scholars instructor course preview.">
    <section class="bg-white py-16">
        <div class="mk-container">
            <div class="overflow-hidden rounded-mk-lg border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="p-6 sm:p-8">
                        <x-section-header
                            eyebrow="Course preview"
                            :title="$course->title"
                            :description="$course->short_description ?? 'Read-only course summary for instructors.'"
                        />
                        <div class="mt-6 flex flex-wrap gap-3">
                            <x-button :href="route('instructor.courses.index')" variant="secondary">Back to Courses</x-button>
                            @if ((int) $course->instructor_id === (int) auth()->id())
                                <x-button :href="route('instructor.courses.edit', $course)">Edit in Studio</x-button>
                            @endif
                        </div>
                    </div>
                    <div class="relative min-h-72 overflow-hidden bg-mk-navy">
                        @if ($course->coverImageUrl())
                            <img src="{{ $course->coverImageUrl() }}" alt="{{ $course->title }} cover image" class="h-full min-h-72 w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-mk-navy/75 via-transparent to-transparent"></div>
                        @else
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.30),transparent_34%),linear-gradient(135deg,#073653_0%,#0e4a72_56%,#102a3a_100%)]"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="rounded-mk-md border border-mk-gold/40 bg-white/10 px-4 py-3 text-sm font-black text-mk-gold">No course image yet</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @include('instructor.partials.nav')

            <div class="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <x-card>
                    <x-badge tone="blue">Academy</x-badge>
                    <h2 class="mt-5 text-xl font-bold text-mk-navy">{{ $course->academy?->name ?? 'MK Scholars' }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Status: {{ $course->status }}</p>
                </x-card>
                <x-card>
                    <x-badge tone="green">Students</x-badge>
                    <h2 class="mt-5 text-3xl font-extrabold text-mk-navy">{{ $course->enrollments_count }}</h2>
                    <x-button :href="route('instructor.courses.students', $course)" size="sm" class="mt-5">View Students</x-button>
                </x-card>
                <x-card>
                    <x-badge tone="gold">Content</x-badge>
                    <h2 class="mt-5 text-xl font-bold text-mk-navy">{{ $course->modules_count }} modules</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $course->lessons_count ?? 0 }} lessons, {{ $assignmentsCount }} assignments, {{ $quizzesCount }} quizzes.</p>
                </x-card>
                <x-card>
                    <x-badge tone="gray">Live classes</x-badge>
                    <h2 class="mt-5 text-3xl font-extrabold text-mk-navy">{{ $liveClassesCount }}</h2>
                    <x-button :href="route('instructor.live-classes.index')" size="sm" variant="secondary" class="mt-5">Open Schedule</x-button>
                </x-card>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <x-button :href="route('instructor.courses.students', $course)" variant="secondary">Students</x-button>
                <x-button :href="route('instructor.courses.submissions', $course)" variant="secondary">Submissions</x-button>
                <x-button :href="route('instructor.courses.quiz-attempts', $course)" variant="secondary">Quiz Attempts</x-button>
            </div>

            <x-card class="mt-10">
                <x-badge tone="green">Recent live sessions</x-badge>
                <div class="mt-6 divide-y divide-slate-100 rounded-lg border border-slate-100">
                    @forelse ($recentLiveClasses as $liveClass)
                        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-bold text-mk-navy">{{ $liveClass->title }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $liveClass->starts_at?->format('M j, Y g:i A') ?? 'To be scheduled' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-badge :tone="$liveClass->status === 'live' ? 'green' : 'gray'">{{ $liveClass->status }}</x-badge>
                                <x-badge tone="blue">{{ $liveClass->attendances->count() }} attendance records</x-badge>
                            </div>
                        </div>
                    @empty
                        <p class="p-4 text-sm leading-6 text-slate-600">No live classes are linked to this course yet.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </section>
</x-dashboard-layout>
