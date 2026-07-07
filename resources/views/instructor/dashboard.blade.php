<x-dashboard-layout role="instructor" title="Instructor Dashboard" description="MK Scholars instructor dashboard.">
    <div class="space-y-6">
        <x-section-header
            eyebrow="Instructor dashboard"
            title="Welcome, {{ auth()->user()->name }}"
            description="Track your courses, learners, and everything waiting on your attention."
        />

        {{-- Metric row --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card tone="blue" label="Courses" :value="$coursesCount" :description="$publishedCoursesCount.' published · '.$draftCoursesCount.' draft'" />
            <x-stat-card tone="gold" label="Learners" :value="$studentsCount" description="Active enrolled students." />
            <x-stat-card tone="success" label="Avg completion" :value="$avgCompletionRate.'%'" description="Average lesson completion." />
            <x-stat-card tone="warning" label="To grade" :value="$pendingSubmissionsCount" description="Submissions awaiting review." />
        </div>

        {{-- Needs your attention --}}
        <div class="grid gap-5 lg:grid-cols-2">
            <x-card class="min-w-0">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <x-badge tone="warning">Needs attention</x-badge>
                        <h3 class="mt-3 text-lg font-bold text-mk-navy">Awaiting grading</h3>
                    </div>
                    <x-button :href="route('instructor.courses.index')" size="sm" variant="secondary">Courses</x-button>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse ($recentSubmissions as $submission)
                        <div class="rounded-mk-md border border-slate-100 bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="min-w-0 truncate font-bold text-mk-navy">{{ $submission->assignment?->title ?? 'Assignment' }}</p>
                                <x-status-badge :status="$submission->status" />
                            </div>
                            <p class="mt-0.5 text-sm text-slate-600">{{ $submission->user?->name ?? 'Student' }} · {{ $submission->submitted_at?->diffForHumans() ?? 'Submitted' }}</p>
                        </div>
                    @empty
                        <x-empty-state icon="assignments" title="Nothing to grade" description="Submitted assignments will appear here for review." />
                    @endforelse
                </div>
            </x-card>

            <x-card class="min-w-0">
                <x-badge tone="blue">Newest enrollments</x-badge>
                <h3 class="mt-3 text-lg font-bold text-mk-navy">Recent sign-ups</h3>
                <div class="mt-4 space-y-2">
                    @forelse ($newestEnrollments as $enrollment)
                        <div class="flex items-center justify-between gap-3 rounded-mk-md border border-slate-100 bg-slate-50 p-3">
                            <div class="min-w-0">
                                <p class="truncate font-bold text-mk-navy">{{ $enrollment->user?->name ?? 'Student' }}</p>
                                <p class="truncate text-sm text-slate-600">{{ $enrollment->course?->title ?? 'Course' }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-semibold text-slate-500">{{ $enrollment->enrolled_at?->diffForHumans() }}</span>
                        </div>
                    @empty
                        <x-empty-state icon="courses" title="No enrollments yet" description="New learners joining your courses will show here." />
                    @endforelse
                </div>
            </x-card>
        </div>

        {{-- Course grid --}}
        <div class="space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Your courses</p>
                    <h2 class="mt-1 text-xl font-extrabold text-mk-navy">Course studio</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-button :href="route('instructor.courses.create')" size="sm">Create Course</x-button>
                    <x-button :href="route('instructor.courses.index')" size="sm" variant="secondary">View all</x-button>
                </div>
            </div>

            @if ($courses->isEmpty())
                <x-empty-state
                    icon="courses"
                    title="No courses yet"
                    description="Create your first course draft, then add modules, lessons, quizzes, and assignments."
                    action-label="Create Course"
                    :action-href="route('instructor.courses.create')"
                />
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($courses as $course)
                        @php($isOwner = (int) $course->instructor_id === (int) auth()->id())
                        <x-course-progress-card
                            :course="$course"
                            :href="route('instructor.courses.show', $course)"
                            :status="$course->status"
                            action-label="Manage"
                            action-variant="secondary"
                        >
                            <x-slot:stats>
                                <div>
                                    <p class="text-lg font-black text-mk-navy">{{ $course->enrollments_count }}</p>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Students</p>
                                </div>
                                <div>
                                    <p class="text-lg font-black text-mk-navy">{{ $course->completion_percentage }}%</p>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Complete</p>
                                </div>
                                <div>
                                    <p class="text-lg font-black text-mk-navy">{{ $course->modules_count }}</p>
                                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Modules</p>
                                </div>
                            </x-slot:stats>
                            @if ($isOwner)
                                <x-slot:actions>
                                    <x-button :href="route('instructor.courses.edit', $course)" size="sm">Edit</x-button>
                                </x-slot:actions>
                            @endif
                        </x-course-progress-card>
                    @endforeach
                </div>
                <p class="text-xs leading-5 text-slate-500">Add lessons from a course's <span class="font-semibold">Edit</span> builder. A true "preview as student" player opens in Phase 2 (the player is student-only today).</p>
            @endif
        </div>

        {{-- Upcoming live classes --}}
        <x-card class="min-w-0">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <x-badge tone="success">Schedule</x-badge>
                    <h3 class="mt-3 text-lg font-bold text-mk-navy">Upcoming live classes</h3>
                </div>
                <x-button :href="route('instructor.live-classes.index')" size="sm" variant="secondary">Open schedule</x-button>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($upcomingLiveClasses as $liveClass)
                    <div class="rounded-mk-md border border-slate-100 bg-slate-50 p-4">
                        <x-status-badge :status="$liveClass->status" />
                        <p class="mt-2 font-bold text-mk-navy">{{ $liveClass->title }}</p>
                        <p class="mt-0.5 text-sm text-slate-600">{{ $liveClass->associatedCourse()?->title ?? 'MK Scholars' }}</p>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $liveClass->starts_at?->format('M j, Y g:i A') ?? 'To be scheduled' }}</p>
                    </div>
                @empty
                    <div class="sm:col-span-2 lg:col-span-3">
                        <x-empty-state icon="live" title="No upcoming classes" description="Scheduled live classes you teach will appear here." />
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>
</x-dashboard-layout>
