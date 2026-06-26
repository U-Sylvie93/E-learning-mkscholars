<x-dashboard-layout role="instructor" title="Instructor Courses" description="MK Scholars instructor course preview.">
    <section class="bg-white py-16">
        <div class="mk-container">
            <x-section-header
                eyebrow="Instructor"
                title="My Courses"
                description="Read-only previews for courses connected to your scheduled live classes."
            />

            @include('instructor.partials.nav')

            <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($courses as $course)
                    <x-card>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :tone="$course->status === \App\Models\Course::STATUS_PUBLISHED ? 'green' : 'gray'">{{ $course->status }}</x-badge>
                            <x-badge tone="blue">{{ $course->academy?->name ?? 'MK Scholars' }}</x-badge>
                        </div>
                        <h2 class="mt-5 text-xl font-extrabold text-mk-navy">{{ $course->title }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $course->short_description ?? 'Course details are available in the preview.' }}</p>
                        <div class="mt-5 grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-lg bg-slate-50 p-3">
                                <p class="text-lg font-extrabold text-mk-navy">{{ $course->enrollments_count }}</p>
                                <p class="text-xs font-semibold text-slate-500">Students</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3">
                                <p class="text-lg font-extrabold text-mk-navy">{{ $course->modules_count }}</p>
                                <p class="text-xs font-semibold text-slate-500">Modules</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 p-3">
                                <p class="text-lg font-extrabold text-mk-navy">{{ $course->instructor_live_classes_count }}</p>
                                <p class="text-xs font-semibold text-slate-500">Live</p>
                            </div>
                        </div>
                        <x-button :href="route('instructor.courses.show', $course)" class="mt-6 w-full" variant="secondary">Open Preview</x-button>
                    </x-card>
                @empty
                    <x-card class="md:col-span-2 xl:col-span-3">
                        <h2 class="text-xl font-bold text-mk-navy">No assigned courses</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Courses appear here after an admin schedules you for a live class linked to a course, module, or lesson.</p>
                    </x-card>
                @endforelse
            </div>
        </div>
    </section>
</x-dashboard-layout>

