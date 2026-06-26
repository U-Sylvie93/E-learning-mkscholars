<x-dashboard-layout role="mentor" title="Mentor Check-ins" description="MK Scholars mentor check-ins.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Mentor"
                title="Weekly Check-ins"
                description="Complete check-ins and leave feedback for assigned students."
            />
            <x-badge tone="gray">{{ $checkIns->count() }} check-ins</x-badge>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-5">
            @forelse ($checkIns as $checkIn)
                <x-card>
                    <div class="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :tone="$checkIn->status === 'completed' ? 'green' : 'blue'">{{ $checkIn->status }}</x-badge>
                                @if ($checkIn->mentorAssignment?->course)
                                    <x-badge tone="gray">{{ $checkIn->mentorAssignment->course->title }}</x-badge>
                                @endif
                            </div>
                            <h2 class="mt-4 text-2xl font-extrabold text-mk-navy">{{ $checkIn->topic }}</h2>
                            <p class="mt-2 text-sm font-semibold text-mk-gold">{{ $checkIn->mentorAssignment?->student?->name ?? 'Student' }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                Scheduled: {{ $checkIn->scheduled_at?->format('M j, Y g:i A') ?? 'To be scheduled' }}
                            </p>
                            @if ($checkIn->student_notes)
                                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $checkIn->student_notes }}</p>
                            @endif
                            @if ($checkIn->mentor_feedback)
                                <div class="mt-5 rounded-lg bg-slate-50 p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Feedback</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $checkIn->mentor_feedback }}</p>
                                </div>
                            @endif
                        </div>

                        @if ($checkIn->status !== 'completed')
                            <form method="POST" action="{{ route('mentor.check-ins.complete', $checkIn) }}">
                                @csrf
                                <label class="block">
                                    <span class="text-sm font-bold text-mk-navy">Mentor feedback</span>
                                    <textarea name="mentor_feedback" rows="7" required class="mt-2 w-full rounded-md border border-slate-200 px-4 py-3 text-sm focus:border-mk-gold focus:ring-mk-gold">{{ old('mentor_feedback', $checkIn->mentor_feedback) }}</textarea>
                                </label>
                                <x-button type="submit" class="mt-4 w-full">Mark Completed</x-button>
                            </form>
                        @else
                            <div class="rounded-lg bg-mk-goldSoft p-5">
                                <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Completed</p>
                                <p class="mt-2 text-sm font-bold text-mk-navy">{{ $checkIn->completed_at?->format('M j, Y g:i A') ?? 'Completed' }}</p>
                            </div>
                        @endif
                    </div>
                </x-card>
            @empty
                <x-card>
                    <h2 class="text-xl font-bold text-mk-navy">No check-ins assigned</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Scheduled check-ins for your assigned students will appear here.</p>
                </x-card>
            @endforelse
        </div>
    </section>
</x-dashboard-layout>

