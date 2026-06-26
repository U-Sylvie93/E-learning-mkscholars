<x-dashboard-layout role="student" :title="$currentLesson?->title ?? $course->title" description="MK Scholars course learning page.">
    @php
        $isCompleted = $currentLesson && in_array($currentLesson->id, $completedLessonIds, true);
        $youtubeEmbedUrl = $currentLesson ? \App\Support\YouTubeEmbed::embedUrl($currentLesson->video_url) : null;
        $allLessons = $course->modules->flatMap(fn ($module) => $module->lessons)->values();
        $currentLessonNumber = $currentLesson ? $allLessons->search(fn ($lesson) => $lesson->id === $currentLesson->id) + 1 : null;
        $totalLessons = $allLessons->count();
        $latestQuizAttempt = $currentQuiz?->attempts?->first();
        $quizPassed = $latestQuizAttempt?->status === \App\Models\QuizAttempt::STATUS_PASSED;
        $firstAssignment = $currentAssignments->first();
        $firstAssignmentSubmission = $firstAssignment?->submissions?->first();
        $materialsCount = $upcomingActivities->count();
        $moduleTitle = $currentLesson?->module?->title ?? 'Learning path';
    @endphp

    <div class="space-y-5" data-testid="learning-workspace">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-mk-navy px-5 py-5 text-white sm:px-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-wide text-mk-gold">
                            <span>{{ $course->academy?->name ?? 'MK Scholars' }}</span>
                            <span class="text-white/35">/</span>
                            <span>{{ $moduleTitle }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap items-end gap-3">
                            <h1 class="break-words text-2xl font-black tracking-normal text-white sm:text-3xl">{{ $currentLesson?->title ?? $course->title }}</h1>
                            @if ($currentLessonNumber)
                                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-bold text-slate-100">Lesson {{ $currentLessonNumber }} of {{ $totalLessons }}</span>
                            @endif
                        </div>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-200">{{ $currentLesson?->summary ?: 'Continue your learning path with a focused lesson workspace, quick actions, and progress tools.' }}</p>
                    </div>

                    <div class="grid gap-3 sm:min-w-72">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-bold text-slate-200">Course progress</span>
                            <span class="font-black text-mk-gold">{{ $progress }}%</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-white/15">
                            <div class="h-full rounded-full bg-mk-gold" style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-button :href="route('student.my-courses')" variant="secondary" size="sm">My Courses</x-button>
                            <x-button href="#learning-tools" variant="primary" size="sm">Lesson Tools</x-button>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="border-t border-emerald-100 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-800 sm:px-6">
                    {{ session('status') }}
                </div>
            @endif
        </section>

        <div class="grid gap-5 2xl:grid-cols-[minmax(16rem,18rem)_minmax(0,1fr)_minmax(18rem,21rem)]">
            <aside class="2xl:sticky 2xl:top-24 2xl:self-start" data-testid="learning-sidebar">
                <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" open>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-b border-slate-100 px-4 py-4 marker:hidden" data-testid="learning-sidebar-toggle">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Course content</p>
                            <h2 class="mt-1 text-base font-extrabold text-mk-navy">Learning path</h2>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-mk-navy group-open:hidden">Open</span>
                        <span class="hidden rounded-full bg-mk-goldSoft px-3 py-1 text-xs font-black text-mk-navy group-open:inline-flex">Hide</span>
                    </summary>

                    <div class="max-h-[70vh] overflow-y-auto p-4">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-mk-navy">{{ $course->title }}</p>
                                <x-badge :tone="$progress === 100 ? 'green' : 'gold'">{{ $progress }}%</x-badge>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-white">
                                <div class="h-2 rounded-full bg-mk-gold" style="width: {{ $progress }}%"></div>
                            </div>
                            <p class="mt-3 text-xs font-semibold text-slate-500">{{ count($completedLessonIds) }} of {{ $totalLessons }} lessons completed</p>
                        </div>

                        <div class="mt-5 space-y-5">
                            @forelse ($course->modules as $module)
                                <section>
                                    <h3 class="break-words text-xs font-black uppercase tracking-wide text-slate-500">{{ $module->title }}</h3>
                                    <div class="mt-3 space-y-2">
                                        @forelse ($module->lessons as $lesson)
                                            @php
                                                $lessonCompleted = in_array($lesson->id, $completedLessonIds, true);
                                                $isCurrent = $currentLesson?->id === $lesson->id;
                                            @endphp
                                            <a
                                                href="{{ route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]) }}"
                                                class="group flex items-start gap-3 rounded-xl border p-3 transition {{ $isCurrent ? 'border-mk-gold bg-mk-goldSoft shadow-sm' : 'border-slate-100 bg-white hover:border-mk-gold/60 hover:bg-slate-50' }}"
                                                @if ($isCurrent) aria-current="page" @endif
                                            >
                                                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black {{ $lessonCompleted ? 'bg-emerald-100 text-emerald-800' : ($isCurrent ? 'bg-mk-gold text-mk-navy' : 'bg-slate-100 text-slate-500') }}">
                                                    {{ $lessonCompleted ? '✓' : $loop->iteration }}
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block break-words text-sm font-extrabold text-mk-navy">{{ $lesson->title }}</span>
                                                    <span class="mt-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                        {{ str_replace('_', ' ', $lesson->lesson_type) }}
                                                        @if ($lesson->duration_minutes)
                                                            · {{ $lesson->duration_minutes }} min
                                                        @endif
                                                    </span>
                                                    @if ($isCurrent)
                                                        <span class="mt-2 inline-flex rounded-full bg-white px-2 py-1 text-xs font-black text-mk-gold">Current lesson</span>
                                                    @elseif ($lessonCompleted)
                                                        <span class="mt-2 inline-flex text-xs font-bold text-emerald-700">Completed</span>
                                                    @endif
                                                </span>
                                            </a>
                                        @empty
                                            <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Lessons will appear here soon.</p>
                                        @endforelse
                                    </div>
                                </section>
                            @empty
                                <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-600">This course does not have published modules yet.</p>
                            @endforelse
                        </div>
                    </div>
                </details>
            </aside>

            <main class="min-w-0 space-y-5" data-testid="learning-main-content">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    @if ($currentLesson)
                        <nav class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500" aria-label="Breadcrumb">
                            <span>{{ $course->title }}</span>
                            <span class="text-slate-300">/</span>
                            <span>{{ $moduleTitle }}</span>
                            <span class="text-slate-300">/</span>
                            <span class="text-mk-gold">{{ $currentLesson->title }}</span>
                        </nav>

                        <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge tone="blue">{{ str_replace('_', ' ', $currentLesson->lesson_type) }}</x-badge>
                                    <x-badge tone="gray">{{ $currentLesson->duration_minutes ? $currentLesson->duration_minutes.' min' : 'Flexible' }}</x-badge>
                                    <x-badge :tone="$isCompleted ? 'green' : 'gold'">{{ $isCompleted ? 'Completed' : 'In progress' }}</x-badge>
                                </div>
                                <h2 class="mt-4 break-words text-3xl font-black tracking-normal text-mk-navy sm:text-4xl">{{ $currentLesson->title }}</h2>
                                @if ($currentLesson->summary)
                                    <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">{{ $currentLesson->summary }}</p>
                                @endif
                            </div>

                            @if ($isCompleted)
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-800">Lesson Completed</div>
                            @else
                                <form method="POST" action="{{ route('student.lessons.complete', [$course, $currentLesson]) }}" class="shrink-0">
                                    @csrf
                                    <x-button type="submit" class="w-full lg:w-auto">Mark Lesson Complete</x-button>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="py-16 text-center">
                            <x-badge tone="gray">No lesson selected</x-badge>
                            <h2 class="mt-4 text-2xl font-extrabold text-mk-navy">No published lesson yet</h2>
                            <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-600">Published lessons will appear here when this course is ready for students.</p>
                        </div>
                    @endif
                </section>

                @if ($currentLesson)
                    @if ($youtubeEmbedUrl)
                        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" data-testid="learning-video-card">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Video lesson</p>
                                    <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Watch and follow along</h3>
                                </div>
                                <button type="button" class="inline-flex items-center rounded-lg border border-mk-gold/40 bg-mk-goldSoft px-4 py-2 text-sm font-black text-mk-navy transition hover:border-mk-gold" data-testid="learning-video-fullscreen" onclick="document.getElementById('learning-video-frame')?.requestFullscreen?.()">
                                    Fullscreen
                                </button>
                            </div>
                            <div id="learning-video-frame" class="bg-mk-navy p-2 sm:p-3">
                                <div class="aspect-video overflow-hidden rounded-xl bg-black">
                                    <iframe
                                        class="h-full w-full"
                                        src="{{ $youtubeEmbedUrl }}"
                                        title="{{ $currentLesson->title }} video lesson"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        referrerpolicy="strict-origin-when-cross-origin"
                                        allowfullscreen
                                    ></iframe>
                                </div>
                            </div>
                        </section>
                    @elseif ($currentLesson->lesson_type === 'video')
                        <section class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center" data-testid="learning-video-card">
                            <p class="text-sm font-black uppercase tracking-wide text-mk-gold">Video lesson</p>
                            <p class="mt-3 text-sm leading-6 text-slate-600">The video link for this lesson is not available yet.</p>
                        </section>
                    @endif

                    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap gap-2 border-b border-slate-100 px-5 py-4">
                            <span class="rounded-full bg-mk-navy px-4 py-2 text-sm font-black text-white">Lesson Notes</span>
                            <a href="#learning-materials" class="rounded-full bg-slate-100 px-4 py-2 text-sm font-black text-slate-600 transition hover:bg-mk-goldSoft hover:text-mk-navy">Resources</a>
                        </div>
                        <div class="p-5 sm:p-6">
                            @if ($currentLesson->content)
                                <div class="whitespace-pre-wrap break-words text-base leading-8 text-slate-700">{{ $currentLesson->content }}</div>
                            @else
                                <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-600">Lesson notes will appear here when they are added by the instructor.</p>
                            @endif
                        </div>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="grid gap-3 md:grid-cols-[1fr_auto_1fr] md:items-center">
                            <div>
                                @if ($previousLesson)
                                    <x-button :href="route('student.courses.learn', ['course' => $course, 'lesson' => $previousLesson->id])" variant="secondary" class="w-full md:w-auto">Previous: {{ $previousLesson->title }}</x-button>
                                @else
                                    <p class="rounded-lg bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500">This is the first lesson.</p>
                                @endif
                            </div>
                            <p class="text-center text-xs font-black uppercase tracking-wide text-slate-500">Lesson {{ $currentLessonNumber ?? 0 }} / {{ $totalLessons }}</p>
                            <div class="md:text-right">
                                @if ($nextLesson)
                                    <x-button :href="route('student.courses.learn', ['course' => $course, 'lesson' => $nextLesson->id])" variant="navy" class="w-full md:w-auto">Next: {{ $nextLesson->title }}</x-button>
                                @else
                                    <x-button :href="route('student.my-courses')" variant="secondary" class="w-full md:w-auto">Back to My Courses</x-button>
                                @endif
                            </div>
                        </div>
                    </section>
                @endif
            </main>

            <aside id="learning-tools" class="space-y-4 2xl:sticky 2xl:top-24 2xl:self-start" data-testid="learning-tools-panel">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Quiz</p>
                            <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Knowledge check</h3>
                        </div>
                        @if ($currentQuiz)
                            <x-badge :tone="$quizPassed ? 'green' : 'blue'">{{ $latestQuizAttempt ? str_replace('_', ' ', $latestQuizAttempt->status) : 'Ready' }}</x-badge>
                        @endif
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $currentQuiz?->title ?? 'No quiz is attached to this lesson.' }}</p>
                    @if ($currentQuiz)
                        <p class="mt-2 text-xs font-semibold text-slate-500">{{ $currentQuiz->questions_count ?? 0 }} questions @if ($latestQuizAttempt) · Latest: {{ $latestQuizAttempt->percentage }}% {{ $latestQuizAttempt->status }} @endif</p>
                        <x-button :href="route('student.quizzes.show', $currentQuiz)" size="sm" class="mt-4 w-full">{{ $latestQuizAttempt ? 'Review or Retake Quiz' : 'Start Quiz' }}</x-button>
                    @endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Assignment</p>
                            <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Practice task</h3>
                        </div>
                        @if ($firstAssignmentSubmission)
                            <x-badge :tone="$firstAssignmentSubmission->status === 'graded' ? 'green' : 'gold'">{{ str_replace('_', ' ', $firstAssignmentSubmission->status) }}</x-badge>
                        @elseif ($firstAssignment)
                            <x-badge tone="blue">Pending</x-badge>
                        @endif
                    </div>
                    @if ($firstAssignment)
                        <p class="mt-3 break-words text-sm font-bold text-mk-navy">{{ $firstAssignment->title }}</p>
                                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $currentAssignments->count() }} assignment{{ $currentAssignments->count() === 1 ? '' : 's' }} attached to this lesson.</p>
                        @if ($firstAssignment->questions->count())
                            <p class="mt-2 text-xs font-semibold text-slate-500">{{ $firstAssignment->questions->count() }} questions</p>
                        @endif
                        @if ($firstAssignment->instruction_file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($firstAssignment->instruction_file_path))
                            <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Document</p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($firstAssignment->instruction_file_path) }}" class="mt-2 inline-flex text-sm font-bold text-mk-navy underline decoration-mk-gold underline-offset-4" target="_blank" rel="noopener noreferrer">View assignment document</a>
                            </div>
                        @endif
                        <x-button :href="route('student.assignments.show', $firstAssignment)" size="sm" variant="secondary" class="mt-4 w-full">{{ $firstAssignmentSubmission ? 'View submission' : 'Open Assignment' }}</x-button>
                    @else
                        <p class="mt-3 text-sm leading-6 text-slate-600">No assignments are attached to this lesson yet.</p>
                    @endif
                </section>

                <section id="learning-materials" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Materials</p>
                    <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Resources</h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($upcomingActivities as $activity)
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <p class="text-xs font-black uppercase tracking-wide text-mk-gold">{{ $activity->activity_type ?? $activity->type }}</p>
                                <p class="mt-1 break-words text-sm font-bold text-mk-navy">{{ $activity->title }}</p>
                                @if ($activity->instructions)
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $activity->instructions }}</p>
                                @endif
                                @if ($activity->resource_url)
                                    <a href="{{ $activity->resource_url }}" class="mt-3 inline-flex text-sm font-bold text-mk-navy underline decoration-mk-gold underline-offset-4" target="_blank" rel="noopener noreferrer">Open resource</a>
                                @endif
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-600">No materials attached.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Progress</p>
                            <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Completion</h3>
                        </div>
                        <x-badge :tone="$completion->is_eligible_for_certificate ? 'green' : 'gold'">{{ $completion->is_eligible_for_certificate ? 'Eligible' : 'In progress' }}</x-badge>
                    </div>
                    <div class="mt-4 flex items-end justify-between gap-3">
                        <p class="text-3xl font-black text-mk-navy">{{ $completionChecklist['lessons']['percentage'] }}%</p>
                        <p class="text-right text-xs font-semibold text-slate-500">Lessons completed</p>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-mk-gold" style="width: {{ $completionChecklist['lessons']['percentage'] }}%"></div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Quizzes</span><span class="font-bold text-mk-navy">{{ $completionChecklist['quizzes']['percentage'] }}%</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Assignments</span><span class="font-bold text-mk-navy">{{ $completionChecklist['assignments']['percentage'] }}%</span></div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Live classes</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($currentLiveClasses as $liveClass)
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge :tone="$liveClass->status === 'live' ? 'green' : 'gray'">{{ $liveClass->status }}</x-badge>
                                    <x-badge tone="blue">{{ str_replace('_', ' ', $liveClass->platform) }}</x-badge>
                                </div>
                                <p class="mt-2 text-sm font-bold text-mk-navy">{{ $liveClass->title }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $liveClass->starts_at->format('M j, Y g:i A') }}</p>
                                @if ($liveClass->status === 'completed' && $liveClass->recording_url)
                                    <x-button :href="$liveClass->recording_url" size="sm" variant="secondary" class="mt-3 w-full">Watch Recording</x-button>
                                @elseif ($liveClass->status !== 'completed')
                                    <form method="POST" action="{{ route('student.live-classes.join', $liveClass) }}" class="mt-3">
                                        @csrf
                                        <x-button type="submit" size="sm" class="w-full">{{ $liveClass->status === 'live' ? 'Join Class' : 'Open Link' }}</x-button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm leading-6 text-slate-600">No live classes are scheduled yet.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Feedback</p>
                            <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Course review</h3>
                        </div>
                        @if ($studentReview)
                            <x-badge :tone="$studentReview->status === \App\Models\CourseReview::STATUS_PUBLISHED ? 'green' : 'gold'">{{ $studentReview->status }}</x-badge>
                        @endif
                    </div>

                    @if ($studentReview)
                        <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="text-sm font-black text-mk-navy">{{ $studentReview->rating }} / 5 stars</p>
                            @if ($studentReview->comment)
                                <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-600">{{ $studentReview->comment }}</p>
                            @else
                                <p class="mt-2 text-sm leading-6 text-slate-600">Your rating has been submitted for moderation.</p>
                            @endif
                        </div>
                    @elseif ($canReviewCourse)
                        <form method="POST" action="{{ route('student.course-reviews.store', $course) }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <label for="course-review-rating" class="text-xs font-black uppercase tracking-wide text-slate-500">Rating</label>
                                <select id="course-review-rating" name="rating" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-mk-navy focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                                    <option value="">Choose rating</option>
                                    @for ($rating = 5; $rating >= 1; $rating--)
                                        <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ $rating }} star{{ $rating === 1 ? '' : 's' }}</option>
                                    @endfor
                                </select>
                                @error('rating')
                                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="course-review-comment" class="text-xs font-black uppercase tracking-wide text-slate-500">Comment</label>
                                <textarea id="course-review-comment" name="comment" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="Share what helped you learn.">{{ old('comment') }}</textarea>
                                @error('comment')
                                    <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <x-button type="submit" size="sm" variant="secondary" class="w-full">Submit Review</x-button>
                        </form>
                    @else
                        <p class="mt-3 text-sm leading-6 text-slate-600">Complete your course access before leaving feedback.</p>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-dashboard-layout>