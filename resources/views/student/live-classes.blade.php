<x-dashboard-layout role="student" title="Live Classes" description="MK Scholars student live classes.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Student"
                title="Live Classes"
                description="Join scheduled sessions for your enrolled MK Scholars courses."
            />
            <x-badge tone="gray">{{ $liveClasses->count() }} sessions</x-badge>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            @if ($liveClasses->isEmpty())
                <x-card>
                    <h2 class="text-xl font-bold text-mk-navy">No live classes yet</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Scheduled sessions for your enrolled courses will appear here.</p>
                    <x-button :href="route('student.my-courses')" class="mt-6">Back to My Courses</x-button>
                </x-card>
            @else
                <div class="grid gap-5 lg:grid-cols-2">
                    @foreach ($liveClasses as $liveClass)
                        @php
                            $course = $liveClass->associatedCourse();
                            $attendance = $liveClass->attendances->first();
                        @endphp
                        <x-card class="flex h-full flex-col">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :tone="$liveClass->status === 'live' ? 'green' : 'gray'">{{ $liveClass->status }}</x-badge>
                                <x-badge tone="blue">{{ str_replace('_', ' ', $liveClass->platform) }}</x-badge>
                                @if ($attendance)
                                    <x-badge tone="gold">{{ $attendance->status }}</x-badge>
                                @endif
                            </div>

                            <h2 class="mt-5 text-xl font-bold text-mk-navy">{{ $liveClass->title }}</h2>
                            <p class="mt-2 text-sm font-semibold text-mk-gold">{{ $course?->title ?? 'MK Scholars' }}</p>
                            @if ($liveClass->description)
                                <p class="mt-4 text-sm leading-6 text-slate-600">{{ $liveClass->description }}</p>
                            @endif

                            <div class="mt-6 grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Starts</p>
                                    <p class="mt-1 font-bold text-mk-navy">{{ $liveClass->starts_at->format('M j, Y g:i A') }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Ends</p>
                                    <p class="mt-1 font-bold text-mk-navy">{{ $liveClass->ends_at->format('M j, Y g:i A') }}</p>
                                </div>
                            </div>

                            <div class="mt-6">
                                @if ($liveClass->status === 'completed' && $liveClass->recording_url)
                                    <x-button :href="$liveClass->recording_url" class="w-full" variant="secondary">Watch Recording</x-button>
                                @elseif ($liveClass->status === 'completed')
                                    <div class="rounded-lg bg-slate-50 p-4 text-center text-sm font-semibold text-slate-500">Recording unavailable</div>
                                @else
                                    <form method="POST" action="{{ route('student.live-classes.join', $liveClass) }}">
                                        @csrf
                                        <x-button type="submit" class="w-full">{{ $liveClass->status === 'live' ? 'Join Class' : 'Open Meeting Link' }}</x-button>
                                    </form>
                                @endif
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-dashboard-layout>

