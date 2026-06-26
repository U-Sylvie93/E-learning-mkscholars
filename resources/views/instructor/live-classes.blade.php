<x-dashboard-layout role="instructor" title="Instructor Live Classes" description="MK Scholars instructor live classes.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Instructor"
                title="Live Classes"
                description="Review your assigned live sessions and student attendance."
            />
            <x-badge tone="gray">{{ $liveClasses->count() }} assigned sessions</x-badge>
        </div>
        <div class="mk-container mt-8">
            @include('instructor.partials.nav')
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            @forelse ($liveClasses as $liveClass)
                @php($course = $liveClass->associatedCourse())

                <x-card class="mb-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :tone="$liveClass->status === \App\Models\LiveClass::STATUS_LIVE ? 'green' : 'gray'">{{ $liveClass->status }}</x-badge>
                                <x-badge tone="blue">{{ str_replace('_', ' ', $liveClass->platform) }}</x-badge>
                            </div>
                            <h2 class="mt-4 text-2xl font-extrabold text-mk-navy">{{ $liveClass->title }}</h2>
                            <p class="mt-2 text-sm font-semibold text-mk-gold">{{ $course?->title ?? 'Unlinked live session' }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                {{ $liveClass->starts_at?->format('M j, Y g:i A') ?? 'To be scheduled' }}
                                @if ($liveClass->ends_at)
                                    - {{ $liveClass->ends_at->format('g:i A') }}
                                @endif
                            </p>
                        </div>
                        @if ($liveClass->meeting_url)
                            <x-button :href="$liveClass->meeting_url" variant="secondary">Open Meeting</x-button>
                        @endif
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
            @empty
                <x-card>
                    <h2 class="text-xl font-bold text-mk-navy">No assigned live classes</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Assigned live classes will appear here after an admin schedules them.</p>
                </x-card>
            @endforelse
        </div>
    </section>
</x-dashboard-layout>

