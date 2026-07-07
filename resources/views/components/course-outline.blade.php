@props([
    'course',
    'completedLessonIds' => [],
    'currentLesson' => null,
    'progress' => 0,
])

@php
    $allLessons = $course->modules->flatMap(fn ($module) => $module->lessons)->values();
    $totalLessons = $allLessons->count();
    $completedCount = collect($completedLessonIds)->intersect($allLessons->pluck('id'))->count();
    $nextUp = $allLessons->first(fn ($lesson) => ! in_array($lesson->id, $completedLessonIds, true));
    $globalIndex = 0;
@endphp

<div class="flex h-full flex-col">
    <div class="border-b border-slate-100 p-4">
        <a href="{{ route('student.my-courses') }}" class="mk-focus inline-flex items-center gap-2 rounded-md text-sm font-bold text-slate-500 transition hover:text-mk-navy">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Back to My Courses
        </a>
        <p class="mt-3 line-clamp-2 text-sm font-black text-mk-navy">{{ $course->title }}</p>
        <div class="mt-3 flex items-center justify-between text-xs font-bold">
            <span class="text-slate-500">{{ $completedCount }} / {{ $totalLessons }} lessons</span>
            <span class="text-mk-navy">{{ $progress }}%</span>
        </div>
        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-mk-gold transition-[width] duration-500" style="width: {{ $progress }}%"></div>
        </div>
        @if ($nextUp)
            <a href="{{ route('student.courses.learn', ['course' => $course, 'lesson' => $nextUp->id]) }}" class="mk-focus mt-3 flex items-center justify-between gap-2 rounded-mk-md bg-mk-goldSoft px-3 py-2 text-xs font-black text-mk-navy transition hover:brightness-95">
                <span class="min-w-0 truncate">Next up: {{ $nextUp->title }}</span>
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        @endif
    </div>

    <nav class="flex-1 overflow-y-auto p-3" aria-label="Course outline">
        <div class="space-y-5">
            @forelse ($course->modules as $module)
                <section>
                    <h3 class="px-1 text-xs font-black uppercase tracking-wide text-slate-500">{{ $module->title }}</h3>
                    <div class="mt-2 space-y-1.5">
                        @forelse ($module->lessons as $lesson)
                            @php
                                $globalIndex++;
                                $lessonCompleted = in_array($lesson->id, $completedLessonIds, true);
                                $isCurrent = $currentLesson?->id === $lesson->id;
                                $hasQuiz = $lesson->relationLoaded('quizzes') && $lesson->quizzes->isNotEmpty();
                                $hasAssignment = $lesson->relationLoaded('assignments') && $lesson->assignments->isNotEmpty();
                            @endphp
                            <a
                                href="{{ route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]) }}"
                                @if ($isCurrent) aria-current="page" @endif
                                class="mk-focus flex items-start gap-3 rounded-mk-md border p-2.5 transition {{ $isCurrent ? 'border-mk-gold bg-mk-goldSoft' : 'border-transparent hover:border-slate-200 hover:bg-slate-50' }}"
                            >
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-black {{ $lessonCompleted ? 'bg-mk-success text-white' : ($isCurrent ? 'bg-mk-gold text-mk-navy' : 'bg-slate-100 text-slate-500') }}">
                                    @if ($lessonCompleted)
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    @else
                                        {{ $globalIndex }}
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block break-words text-sm font-bold {{ $isCurrent ? 'text-mk-navy' : 'text-slate-700' }}">{{ $lesson->title }}</span>
                                    <span class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                        <span>{{ str_replace('_', ' ', $lesson->lesson_type) }}</span>
                                        @if ($lesson->duration_minutes)<span>· {{ $lesson->duration_minutes }}m</span>@endif
                                        @if ($hasQuiz)<span class="rounded-full bg-sky-50 px-1.5 py-0.5 text-sky-700">Quiz</span>@endif
                                        @if ($hasAssignment)<span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-slate-600">Task</span>@endif
                                    </span>
                                </span>
                            </a>
                        @empty
                            <p class="rounded-mk-md border border-dashed border-slate-200 bg-slate-50 p-2.5 text-xs text-slate-500">No lessons yet.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <p class="rounded-mk-md border border-dashed border-slate-200 bg-slate-50 p-3 text-sm leading-6 text-slate-500">This course has no published modules yet.</p>
            @endforelse
        </div>
    </nav>
</div>
