<x-dashboard-layout role="instructor" title="Instructor Live Classes" description="MK Scholars instructor live classes.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Instructor"
                title="Live Classes"
                description="Create, edit, and monitor live sessions for your assigned courses."
            />
            <div class="flex flex-wrap gap-3">
                <x-badge tone="gray">{{ $liveClasses->count() }} sessions</x-badge>
                @if ($courses->isNotEmpty())
                    <x-button :href="route('instructor.live-classes.create')">Add Live Class</x-button>
                @endif
            </div>
        </div>
        <div class="mk-container mt-8">
            @include('instructor.partials.nav')
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->has('live_class'))
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">{{ $errors->first('live_class') }}</div>
            @endif

            @if ($courses->isEmpty())
                <x-card>
                    <h2 class="text-xl font-bold text-mk-navy">No assigned courses</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Create or receive access to a course before scheduling live classes.</p>
                    <x-button :href="route('instructor.courses.index')" class="mt-6">My Courses</x-button>
                </x-card>
            @elseif ($liveClasses->isEmpty())
                <x-card>
                    <x-badge tone="gray">Class Schedule</x-badge>
                    <h2 class="mt-4 text-xl font-bold text-mk-navy">No live classes yet</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Schedule your first live session for one of your assigned courses.</p>
                    <x-button :href="route('instructor.live-classes.create')" class="mt-6">Add Live Class</x-button>
                </x-card>
            @else
                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($liveClasses as $liveClass)
                        @php($course = $liveClass->associatedCourse())

                        <x-card class="flex h-full flex-col">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-badge :tone="$liveClass->displayStatusTone()">{{ $liveClass->displayStatus() }}</x-badge>
                                        <x-badge tone="blue">{{ str_replace('_', ' ', $liveClass->platform) }}</x-badge>
                                    </div>
                                    <h2 class="mt-4 break-words text-2xl font-extrabold text-mk-navy">{{ $liveClass->title }}</h2>
                                    <p class="mt-2 text-sm font-semibold text-mk-gold">{{ $course?->title ?? 'Unlinked live session' }}</p>
                                    @if ($liveClass->description)
                                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $liveClass->description }}</p>
                                    @endif
                                </div>
                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <x-button :href="route('instructor.live-classes.edit', $liveClass)" size="sm">Edit Live Class</x-button>
                                    @if ($liveClass->status !== \App\Models\LiveClass::STATUS_CANCELLED)
                                        <form method="POST" action="{{ route('instructor.live-classes.cancel', $liveClass) }}">
                                            @csrf
                                            <x-button type="submit" size="sm" variant="secondary">Cancel</x-button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-6 flex flex-wrap gap-2">
                                @if ($liveClass->canJoin())
                                    <x-button :href="route('instructor.live-classes.join', $liveClass)" size="sm">Join Class</x-button>
                                @elseif ($liveClass->canWatchRecording())
                                    <x-button :href="route('instructor.live-classes.recording', $liveClass)" size="sm" variant="secondary">Watch Recording</x-button>
                                    <x-button :href="route('instructor.live-classes.edit', $liveClass)" size="sm" variant="ghost">Edit Recording</x-button>
                                @elseif ($liveClass->isEnded() && $liveClass->status !== \App\Models\LiveClass::STATUS_CANCELLED)
                                    <x-button :href="route('instructor.live-classes.edit', $liveClass)" size="sm" variant="secondary">Add Recording</x-button>
                                @elseif ($liveClass->isUpcoming())
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600">Upcoming</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600">Cancelled</span>
                                @endif
                            </div>

                            <div class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Start Time</p>
                                    <p class="mt-1 font-bold text-mk-navy">{{ $liveClass->starts_at?->format('M j, Y g:i A') }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">End Time</p>
                                    <p class="mt-1 font-bold text-mk-navy">{{ $liveClass->ends_at?->format('M j, Y g:i A') }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Join URL</p>
                                    <p class="mt-1 font-semibold text-slate-500">{{ $liveClass->canJoin() ? 'Available through Join Class' : 'Available during class time' }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Recording URL</p>
                                    @if ($liveClass->recording_url)
                                        <p class="mt-1 font-semibold text-slate-500">{{ $liveClass->canWatchRecording() ? 'Recording available' : 'Added; available after class ends' }}</p>
                                    @else
                                        <p class="mt-1 font-semibold text-slate-500">Not added</p>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-6">
                                <h3 class="text-lg font-extrabold text-mk-navy">Attendance</h3>
                                <div class="mt-4 divide-y divide-slate-100 rounded-lg border border-slate-100">
                                    @forelse ($liveClass->attendances as $attendance)
                                        <div class="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="font-bold text-mk-navy">{{ $attendance->user?->name ?? 'Student' }}</p>
                                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $attendance->user?->email }}</p>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-badge :tone="$attendance->status === 'attended' ? 'green' : 'gray'">{{ $attendance->status }}</x-badge>
                                                @if ($attendance->joined_at)
                                                    <span class="text-xs font-semibold text-slate-500">Joined {{ $attendance->joined_at->format('M j, g:i A') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="p-4 text-sm text-slate-600">No attendance has been recorded yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-dashboard-layout>
