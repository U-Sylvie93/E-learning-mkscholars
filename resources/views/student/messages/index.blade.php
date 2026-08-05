<x-dashboard-layout role="student" title="Messages" description="Chat with your course instructors.">
    <div class="space-y-6">
        <x-section-header
            eyebrow="Chat rooms"
            title="Course messages"
            description="Ask your instructor questions and get replies inside the course chat."
        />

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($threads->isEmpty() && $enrolledCoursesWithoutThread->isEmpty())
            <x-card>
                <x-empty-state
                    title="No conversations yet"
                    description="Enroll in a course with an assigned instructor to start a chat."
                />
                <div class="mt-4"><x-button :href="route('student.my-courses')" size="sm">Go to My Courses</x-button></div>
            </x-card>
        @endif

        @if ($threads->isNotEmpty())
            <div class="grid gap-4">
                @foreach ($threads as $thread)
                    @php($last = $thread->messages->first())
                    <a href="{{ route('student.messages.show', $thread->course_id) }}" class="block">
                        <x-card class="transition hover:border-mk-gold hover:shadow-soft">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">{{ $thread->course?->academy?->name ?? 'Course' }}</p>
                                    <h3 class="mt-1 text-lg font-black text-mk-navy">{{ $thread->course?->title }}</h3>
                                    <p class="mt-1 text-sm font-semibold text-slate-600">With {{ $thread->instructor?->name ?? 'Instructor' }}</p>
                                </div>
                                @if ($thread->last_message_at)
                                    <span class="text-xs font-bold text-slate-500">{{ $thread->last_message_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            @if ($last)
                                <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $last->body }}</p>
                            @else
                                <p class="mt-3 text-sm italic leading-6 text-slate-500">No messages yet — say hi to your instructor.</p>
                            @endif
                        </x-card>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($enrolledCoursesWithoutThread->isNotEmpty())
            <div>
                <x-section-header eyebrow="Start a chat" title="Other enrolled courses" />
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @foreach ($enrolledCoursesWithoutThread as $course)
                        <x-card>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">{{ $course->academy?->name ?? 'Course' }}</p>
                            <h3 class="mt-1 text-lg font-black text-mk-navy">{{ $course->title }}</h3>
                            <p class="mt-1 text-xs font-semibold text-slate-500">Instructor: {{ $course->instructor?->name }}</p>
                            <div class="mt-4">
                                <x-button :href="route('student.messages.show', $course->id)" size="sm">Open chat</x-button>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-dashboard-layout>
