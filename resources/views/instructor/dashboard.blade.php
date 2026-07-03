<x-dashboard-layout role="instructor" title="Instructor Dashboard" description="MK Scholars instructor dashboard.">
    <div class="space-y-6">
        <x-section-header
            eyebrow="Instructor dashboard"
            title="Teaching command center"
            description="Build your owned courses and review learners, submissions, quizzes, and live teaching schedule."
        />

        <x-card highlighted class="min-w-0">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <x-badge tone="gold">Teaching workspace</x-badge>
                    <h2 class="mt-4 text-2xl font-extrabold text-mk-navy">Welcome, {{ auth()->user()->name }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Manage your owned and assigned courses, live classes, learner activity, submissions, and platform updates from one role-focused dashboard.</p>
                </div>
                <div class="grid w-full gap-3 sm:w-auto sm:grid-cols-2">
                    <x-button :href="route('instructor.courses.index')">View Courses</x-button>
                    <x-button :href="route('instructor.notifications')" variant="secondary">Notifications</x-button>
                </div>
            </div>
        </x-card>

        <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <x-stat-card label="Classes" :value="$coursesCount" description="Owned courses plus assigned live-teaching courses." />
            <x-stat-card tone="success" label="Learners" :value="$studentsCount" description="Active enrolled students across your assigned courses." />
            <x-stat-card label="Submissions" :value="$pendingSubmissionsCount" description="Pending assignment submissions for review." />
            <x-stat-card tone="blue" label="Quizzes" :value="$passedQuizAttemptsCount.'/'.$quizAttemptsCount" description="Passed quiz attempts out of all attempts." />
            <x-stat-card label="Notifications" value="Updates" description="Review platform messages and class reminders." actionLabel="Open Notifications" :actionHref="route('instructor.notifications')" />
        </div>

        <div class="mt-10 grid gap-6 lg:grid-cols-[1fr_0.9fr]">
            <x-card class="min-w-0">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <x-badge tone="blue">My courses</x-badge>
                        <h2 class="mt-4 text-xl font-bold text-mk-navy">Course studio preview</h2>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-button :href="route('instructor.courses.create')" size="sm">Create Course</x-button>
                        <x-button :href="route('instructor.courses.index')" size="sm" variant="secondary">View All</x-button>
                    </div>
                </div>
                <div class="mt-6 divide-y divide-slate-100 rounded-lg border border-slate-100">
                    @forelse ($courses as $course)
                        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-bold text-mk-navy">{{ $course->title }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $course->academy?->name ?? 'MK Scholars' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-badge tone="gray">{{ $course->enrollments_count }} students</x-badge>
                                <x-badge tone="blue">{{ $course->instructor_live_classes_count }} live</x-badge>
                                <x-button :href="route('instructor.courses.show', $course)" size="sm" variant="secondary">Open</x-button>
                                @if ((int) $course->instructor_id === (int) auth()->id())
                                    <x-button :href="route('instructor.courses.edit', $course)" size="sm">Builder</x-button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <x-empty-state
                            icon="courses"
                            title="No courses yet"
                            description="Create a draft course or ask an admin to assign you to a live class."
                            actionLabel="Create Course"
                            :actionHref="route('instructor.courses.create')"
                        />
                    @endforelse
                </div>
            </x-card>

            <x-card class="min-w-0">
                <x-badge tone="gold">Recent submissions</x-badge>
                <div class="mt-6 space-y-4">
                    @forelse ($recentSubmissions as $submission)
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="font-bold text-mk-navy">{{ $submission->assignment?->title ?? 'Assignment' }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $submission->user?->name ?? 'Student' }} - {{ $submission->submitted_at?->format('M j, Y g:i A') ?? 'Submitted' }}</p>
                        </div>
                    @empty
                        <x-empty-state
                            icon="assignments"
                            title="No recent submissions"
                            description="Submissions for your assigned courses will appear here for review."
                        />
                    @endforelse
                </div>
            </x-card>
        </div>

        <x-card class="mt-10">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <x-badge tone="success">Schedule</x-badge>
                    <h2 class="mt-4 text-xl font-bold text-mk-navy">Upcoming live classes</h2>
                </div>
                <x-button :href="route('instructor.live-classes.index')" size="sm" variant="secondary">Open Schedule</x-button>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($upcomingLiveClasses as $liveClass)
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <x-badge :tone="$liveClass->status === 'live' ? 'success' : 'gray'">{{ $liveClass->status }}</x-badge>
                        <p class="mt-3 font-bold text-mk-navy">{{ $liveClass->title }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $liveClass->associatedCourse()?->title ?? 'MK Scholars' }}</p>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $liveClass->starts_at?->format('M j, Y g:i A') ?? 'To be scheduled' }}</p>
                    </div>
                @empty
                    <div class="md:col-span-2 lg:col-span-3">
                        <x-empty-state
                            icon="live"
                            title="No upcoming live classes"
                            description="Live classes assigned to you will show up here with their schedule."
                        />
                    </div>
                @endforelse
            </div>
        </x-card>
    </div>
</x-dashboard-layout>
