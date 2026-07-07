<x-dashboard-layout role="instructor" title="Instructor Courses" description="MK Scholars instructor course preview.">
    <section class="bg-white py-16">
        <div class="mk-container">
            <x-section-header
                eyebrow="Instructor"
                title="My Courses"
                description="Create your own course drafts and preview courses connected to your live teaching schedule."
            />

            @include('instructor.partials.nav')

            <div class="mt-6 flex justify-end">
                <x-button :href="route('instructor.courses.create')">Create Course</x-button>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($courses as $course)
                    <x-card>
                        <div class="mb-5 overflow-hidden rounded-mk-md border border-slate-200 bg-mk-navy">
                            @if ($course->coverImageUrl())
                                <img src="{{ $course->coverImageUrl() }}" alt="{{ $course->title }} cover image" class="h-44 w-full object-cover">
                            @else
                                <div class="flex h-44 w-full items-center justify-center bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.30),transparent_34%),linear-gradient(135deg,#073653_0%,#0e4a72_56%,#102a3a_100%)]">
                                    <span class="rounded-mk-md border border-mk-gold/40 bg-white/10 px-4 py-3 text-sm font-black text-mk-gold">Course image</span>
                                </div>
                            @endif
                        </div>
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
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <x-button :href="route('instructor.courses.show', $course)" class="w-full" variant="secondary">Open Preview</x-button>
                            @if ((int) $course->instructor_id === (int) auth()->id())
                                <x-button :href="route('instructor.courses.edit', $course)" class="w-full">Builder</x-button>
                            @endif
                        </div>
                    </x-card>
                @empty
                    <x-card class="md:col-span-2 xl:col-span-3">
                        <h2 class="text-xl font-bold text-mk-navy">No assigned courses</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Courses appear here when you create them or when an admin schedules you for a live class linked to a course, module, or lesson.</p>
                    </x-card>
                @endforelse
            </div>
        </div>
    </section>
</x-dashboard-layout>

