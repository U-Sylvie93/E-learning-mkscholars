<x-dashboard-layout role="mentor" title="Assigned Students" description="MK Scholars mentor students.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Mentor"
                title="Assigned Students"
                description="Students currently connected to your mentorship support."
            />
            <x-badge tone="gray">{{ $assignments->count() }} assignments</x-badge>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            @if ($assignments->isEmpty())
                <x-card>
                    <h2 class="text-xl font-bold text-mk-navy">No students assigned</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Assigned students will appear here once admin connects them to you.</p>
                </x-card>
            @else
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($assignments as $assignment)
                        <x-card>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :tone="$assignment->status === 'active' ? 'green' : 'gray'">{{ $assignment->status }}</x-badge>
                                @if ($assignment->course)
                                    <x-badge tone="blue">{{ $assignment->course->title }}</x-badge>
                                @endif
                            </div>
                            <h2 class="mt-5 text-xl font-bold text-mk-navy">{{ $assignment->student?->name ?? 'Student' }}</h2>
                            <p class="mt-2 text-sm text-slate-600">{{ $assignment->student?->email }}</p>
                            <p class="mt-4 text-sm leading-6 text-slate-600">{{ $assignment->notes ?? 'No mentor notes yet.' }}</p>
                            <x-button :href="route('mentor.check-ins')" size="sm" class="mt-5">View Check-ins</x-button>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-dashboard-layout>

