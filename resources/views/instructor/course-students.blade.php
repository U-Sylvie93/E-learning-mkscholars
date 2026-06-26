<x-dashboard-layout role="instructor" :title="$course->title.' Students'" description="MK Scholars instructor student preview.">
    <section class="bg-white py-16">
        <div class="mk-container">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-section-header eyebrow="Students" :title="$course->title" description="Read-only enrollment and progress preview." />
                <x-button :href="route('instructor.courses.show', $course)" variant="secondary">Back to Course</x-button>
            </div>

            @include('instructor.partials.nav')

            <x-card class="mt-10 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Progress</th>
                                <th class="px-4 py-3">Enrolled</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($enrollments as $item)
                                <tr>
                                    <td class="px-4 py-3 font-bold text-mk-navy">{{ $item['student']->name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $item['student']->email }}</td>
                                    <td class="px-4 py-3"><x-badge tone="gray">{{ $item['enrollment']->status }}</x-badge></td>
                                    <td class="px-4 py-3 font-bold text-mk-navy">{{ $item['progress'] }}%</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $item['enrollment']->enrolled_at?->format('M j, Y') ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-sm text-slate-600">No enrolled students found for this course.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </section>
</x-dashboard-layout>

