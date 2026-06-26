<x-dashboard-layout role="student" title="My Assignments" description="MK Scholars student assignments.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Student"
                title="My Assignments"
                description="Track pending, submitted, graded, and resubmission-required assignments."
            />
            <x-badge tone="gray">{{ $assignments->count() }} assignments</x-badge>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            @if ($assignments->isEmpty())
                <x-card>
                    <h2 class="text-xl font-bold text-mk-navy">No assignments yet</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Assignments will appear here when they are published in your enrolled courses.</p>
                    <x-button :href="route('student.my-courses')" class="mt-6">Back to My Courses</x-button>
                </x-card>
            @else
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($assignments as $item)
                        <x-card class="flex h-full flex-col">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :tone="$item['status'] === 'graded' ? 'green' : ($item['status'] === 'pending' ? 'blue' : 'gold')">
                                    {{ str_replace('_', ' ', $item['status']) }}
                                </x-badge>
                                <x-badge tone="gray">{{ $item['assignment']->submission_type }}</x-badge>
                            </div>
                            <h2 class="mt-5 text-xl font-bold text-mk-navy">{{ $item['assignment']->title }}</h2>
                            <p class="mt-2 text-sm font-semibold text-mk-gold">{{ $item['lesson']->title }}</p>
                            <p class="mt-4 text-sm leading-6 text-slate-600">Max score: {{ $item['assignment']->max_score }}</p>
                            @if ($item['submission']?->score !== null)
                                <p class="mt-2 text-sm font-bold text-mk-navy">Score: {{ $item['submission']->score }} / {{ $item['assignment']->max_score }}</p>
                            @endif
                            <x-button :href="route('student.assignments.show', $item['assignment'])" class="mt-6 w-full">
                                {{ $item['status'] === 'pending' || $item['status'] === 'resubmission_required' ? 'Open Assignment' : 'View Submission' }}
                            </x-button>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-dashboard-layout>

