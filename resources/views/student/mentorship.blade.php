<x-dashboard-layout role="student" title="Learning support" description="MK Scholars learning support.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Student"
                title="Learning support"
                description="Stay connected with your support and weekly check-ins."
            />
            <x-badge tone="gray">{{ $assignments->count() }} support assignments</x-badge>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-[1fr_0.8fr]">
            <div class="grid gap-5">
                <h2 class="text-2xl font-extrabold text-mk-navy">Assigned supports</h2>

                @forelse ($assignments as $assignment)
                    <x-card>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge :tone="$assignment->status === 'active' ? 'green' : 'gray'">{{ $assignment->status }}</x-badge>
                                    @if ($assignment->course)
                                        <x-badge tone="blue">{{ $assignment->course->title }}</x-badge>
                                    @endif
                                </div>
                                <h3 class="mt-4 text-xl font-bold text-mk-navy">{{ $assignment->support?->name ?? 'Support' }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Assigned {{ $assignment->assigned_at?->format('M j, Y') }}</p>
                                @if ($assignment->notes)
                                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $assignment->notes }}</p>
                                @endif
                            </div>
                        </div>
                    </x-card>
                @empty
                    <x-card>
                        <h3 class="text-xl font-bold text-mk-navy">No support assigned yet</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">A support will appear here once the MK Scholars team assigns one to support your learning journey.</p>
                    </x-card>
                @endforelse
            </div>

            <div class="grid gap-5">
                <x-card>
                    <h2 class="text-xl font-extrabold text-mk-navy">Upcoming check-ins</h2>
                    <div class="mt-5 space-y-3">
                        @forelse ($upcomingCheckIns as $checkIn)
                            <div class="rounded-lg bg-slate-50 p-4">
                                <x-badge tone="blue">{{ $checkIn->status }}</x-badge>
                                <p class="mt-3 font-bold text-mk-navy">{{ $checkIn->topic }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $checkIn->scheduled_at?->format('M j, Y g:i A') ?? 'To be scheduled' }}</p>
                            </div>
                        @empty
                            <p class="text-sm leading-6 text-slate-600">No upcoming check-ins yet.</p>
                        @endforelse
                    </div>
                </x-card>

                <x-card highlighted>
                    <h2 class="text-xl font-extrabold text-mk-navy">Previous feedback</h2>
                    <div class="mt-5 space-y-3">
                        @forelse ($previousFeedback as $checkIn)
                            <div class="rounded-lg bg-white p-4">
                                <p class="font-bold text-mk-navy">{{ $checkIn->topic }}</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $checkIn->support_feedback }}</p>
                            </div>
                        @empty
                            <p class="text-sm leading-6 text-slate-600">Support feedback will appear here after completed check-ins.</p>
                        @endforelse
                    </div>
                </x-card>
            </div>
        </div>
    </section>
</x-dashboard-layout>


