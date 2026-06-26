<x-dashboard-layout role="instructor" :title="$course->title.' Quiz Attempts'" description="MK Scholars instructor quiz attempts preview.">
    <section class="bg-white py-16">
        <div class="mk-container">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-section-header eyebrow="Quiz attempts" :title="$course->title" description="Read-only quiz performance preview." />
                <x-button :href="route('instructor.courses.show', $course)" variant="secondary">Back to Course</x-button>
            </div>

            @include('instructor.partials.nav')

            <x-card class="mt-10 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Quiz</th>
                                <th class="px-4 py-3">Score</th>
                                <th class="px-4 py-3">Percentage</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Attempt Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($attempts as $attempt)
                                <tr>
                                    <td class="px-4 py-3 font-bold text-mk-navy">{{ $attempt->user?->name ?? 'Student' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $attempt->quiz?->title ?? 'Quiz' }}</td>
                                    <td class="px-4 py-3 font-bold text-mk-navy">{{ $attempt->score }}/{{ $attempt->total_points }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $attempt->percentage }}%</td>
                                    <td class="px-4 py-3"><x-badge :tone="$attempt->status === 'passed' ? 'green' : 'gray'">{{ str_replace('_', ' ', $attempt->status) }}</x-badge></td>
                                    <td class="px-4 py-3 text-slate-600">{{ $attempt->submitted_at?->format('M j, Y g:i A') ?? $attempt->started_at?->format('M j, Y g:i A') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-sm text-slate-600">No quiz attempts found for this course.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </section>
</x-dashboard-layout>

