<x-dashboard-layout role="student" title="My Courses" description="MK Scholars enrolled courses.">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <x-section-header
                eyebrow="Student"
                title="My Courses"
                description="Continue learning from the courses you have enrolled in."
            />
            <x-badge tone="gray">{{ $enrollments->count() }} enrolled</x-badge>
        </div>

        @if ($enrollments->isEmpty())
            <x-empty-state
                icon="courses"
                title="No courses yet"
                description="Explore the catalog and enroll when you are ready to begin learning."
                action-label="Browse Courses"
                :action-href="route('courses')"
            />
        @else
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($enrollments as $item)
                    <x-course-progress-card
                        :course="$item['course']"
                        :href="route('student.courses.learn', $item['course'])"
                        :progress="$item['progress']"
                        action-label="Continue Learning"
                    >
                        <x-slot:meta>
                            <x-badge tone="blue">{{ $item['course']->level }}</x-badge>
                            <x-badge :tone="$item['completion']->is_eligible_for_certificate ? 'success' : 'gray'">
                                {{ $item['completion']->is_eligible_for_certificate ? 'Certificate eligible' : $item['completion']->lesson_percentage.'% lessons' }}
                            </x-badge>
                        </x-slot:meta>
                    </x-course-progress-card>
                @endforeach
            </div>
        @endif
    </div>
</x-dashboard-layout>
