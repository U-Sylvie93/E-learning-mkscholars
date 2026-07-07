<x-course-player-layout
    :title="$currentLesson?->title ?? $course->title"
    description="MK Scholars course player."
    :course="$course"
    :completed-lesson-ids="$completedLessonIds"
    :current-lesson="$currentLesson"
    :progress="$progress"
>
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
        $moduleTitle = $currentLesson?->module?->title ?? 'Learning path';
        $renderedLessonContent = $currentLesson ? \App\Support\CourseContentRenderer::render($currentLesson->content) : '';
    @endphp

    <div class="space-y-5" data-testid="learning-workspace">
        @if (session('status'))
            <div class="rounded-mk-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($currentLesson)
            {{-- Lesson header --}}
            <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <nav class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-400" aria-label="Breadcrumb">
                    <span>{{ $course->title }}</span>
                    <span class="text-slate-300">/</span>
                    <span>{{ $moduleTitle }}</span>
                </nav>
                <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge tone="blue">{{ str_replace('_', ' ', $currentLesson->lesson_type) }}</x-badge>
                            <x-badge tone="gray">{{ $currentLesson->duration_minutes ? $currentLesson->duration_minutes.' min' : 'Flexible' }}</x-badge>
                            @if ($currentLessonNumber)<x-badge tone="gray">Lesson {{ $currentLessonNumber }} / {{ $totalLessons }}</x-badge>@endif
                        </div>
                        <h2 class="mt-3 break-words text-2xl font-black tracking-normal text-mk-navy sm:text-3xl">{{ $currentLesson->title }}</h2>
                        @if ($currentLesson->summary)
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">{{ $currentLesson->summary }}</p>
                        @endif
                    </div>
                    @if ($isCompleted)
                        <span class="inline-flex shrink-0 items-center gap-2 rounded-mk-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-black text-emerald-800">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Lesson Completed
                        </span>
                    @else
                        <form method="POST" action="{{ route('student.lessons.complete', ['course' => $course, 'lesson' => $currentLesson]) }}" class="shrink-0">
                            @csrf
                            <x-button type="submit" size="sm">Mark Lesson Complete</x-button>
                        </form>
                    @endif
                </div>
            </section>

            {{-- Video --}}
            @if ($youtubeEmbedUrl)
                <section class="overflow-hidden rounded-mk-lg border border-slate-200 bg-white shadow-sm" data-testid="learning-video-card">
                    <div class="aspect-video bg-black">
                        <iframe
                            class="h-full w-full"
                            src="{{ $youtubeEmbedUrl }}"
                            title="{{ $currentLesson->title }} video lesson"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                        ></iframe>
                    </div>
                    <div class="border-t border-slate-100 bg-white px-4 py-3 text-right">
                        <a href="{{ $youtubeEmbedUrl }}" target="_blank" rel="noopener noreferrer" class="mk-focus inline-flex rounded-sm text-xs font-black uppercase tracking-wide text-mk-navy underline decoration-mk-gold underline-offset-4" data-testid="learning-video-fullscreen">Open video</a>
                    </div>
                </section>
            @elseif ($currentLesson->lesson_type === 'video')
                <x-empty-state icon="live" title="Video coming soon" description="The video link for this lesson is not available yet." />
            @endif

            {{-- Lesson notes --}}
            <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Lesson notes</p>
                @if ($renderedLessonContent)
                    <div class="mk-rich-content mt-4">
                        {!! $renderedLessonContent !!}
                    </div>
                @else
                    <div class="mt-4 rounded-mk-md border border-dashed border-slate-200 bg-slate-50 p-5">
                        <h3 class="text-base font-extrabold text-mk-navy">No written notes yet</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Use the video, quiz, assignment, or materials attached to this lesson while written notes are being prepared.</p>
                    </div>
                @endif
            </section>

            <aside class="space-y-5" data-testid="learning-tools-panel">
                <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Learning tools</p>
                    <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Lesson Tools</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Open the activities attached to this lesson when you are ready.</p>
                </section>

            {{-- Quiz --}}
            @if ($currentQuiz)
                <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm" data-testid="learning-quiz-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Quiz</p>
                            <h3 class="mt-1 break-words text-lg font-extrabold text-mk-navy">{{ $currentQuiz->title }}</h3>
                            <p class="mt-1 text-xs font-semibold text-slate-500">
                                {{ $currentQuiz->questions_count ?? 0 }} questions
                                @if ($latestQuizAttempt)· Latest: {{ $latestQuizAttempt->percentage }}%@endif
                            </p>
                        </div>
                        @if ($latestQuizAttempt)<x-status-badge :status="$latestQuizAttempt->status" />@else<x-badge tone="blue">Ready</x-badge>@endif
                    </div>
                    <x-button :href="route('student.quizzes.show', $currentQuiz)" size="sm" class="mt-4">{{ $latestQuizAttempt ? 'Review or Retake Quiz' : 'Start Quiz' }}</x-button>
                </section>
            @else
                <section class="rounded-mk-lg border border-dashed border-slate-200 bg-white p-5">
                    <p class="text-sm font-semibold text-slate-600">No quiz is attached to this lesson.</p>
                </section>
            @endif

            {{-- Assignment --}}
            @if ($firstAssignment)
                <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm" data-testid="learning-assignment-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Assignment</p>
                            <h3 class="mt-1 break-words text-lg font-extrabold text-mk-navy">{{ $firstAssignment->title }}</h3>
                            @if ($firstAssignment->questions->count())
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $firstAssignment->questions->count() }} questions</p>
                            @endif
                        </div>
                        @if ($firstAssignmentSubmission)<x-status-badge :status="$firstAssignmentSubmission->status" />@else<x-badge tone="blue">Pending</x-badge>@endif
                    </div>
                    @if ($firstAssignment->instruction_file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($firstAssignment->instruction_file_path))
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($firstAssignment->instruction_file_path) }}" class="mk-focus mt-3 inline-flex rounded-sm text-sm font-bold text-mk-navy underline decoration-mk-gold underline-offset-4" target="_blank" rel="noopener noreferrer">View assignment document</a>
                    @endif
                    <x-button :href="route('student.assignments.show', $firstAssignment)" size="sm" variant="secondary" class="mt-4">{{ $firstAssignmentSubmission ? 'View submission' : 'Open Assignment' }}</x-button>
                </section>
            @else
                <section class="rounded-mk-lg border border-dashed border-slate-200 bg-white p-5">
                    <p class="text-sm font-semibold text-slate-600">No assignments are attached to this lesson yet.</p>
                </section>
            @endif

            {{-- Materials --}}
            @if ($upcomingActivities->isNotEmpty())
                <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Materials</p>
                    <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Resources</h3>
                    <div class="mt-3 space-y-2">
                        @foreach ($upcomingActivities as $activity)
                            <div class="rounded-mk-md border border-slate-100 bg-slate-50 p-3">
                                <p class="text-xs font-black uppercase tracking-wide text-mk-gold">{{ $activity->activity_type ?? $activity->type }}</p>
                                <p class="mt-1 break-words text-sm font-bold text-mk-navy">{{ $activity->title }}</p>
                                @if ($activity->instructions)<p class="mt-1 text-sm leading-6 text-slate-600">{{ $activity->instructions }}</p>@endif
                                @if ($activity->resource_url)
                                    <a href="{{ $activity->resource_url }}" class="mk-focus mt-2 inline-flex rounded-sm text-sm font-bold text-mk-navy underline decoration-mk-gold underline-offset-4" target="_blank" rel="noopener noreferrer">Open resource</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @else
                <section class="rounded-mk-lg border border-dashed border-slate-200 bg-white p-5">
                    <p class="text-sm font-semibold text-slate-600">No materials attached.</p>
                </section>
            @endif

            {{-- Live classes --}}
            @if ($currentLiveClasses->isNotEmpty())
                <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Live classes</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($currentLiveClasses as $liveClass)
                            <div class="rounded-mk-md border border-slate-100 bg-slate-50 p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-status-badge :status="$liveClass->status" />
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
                        @endforeach
                    </div>
                </section>
            @endif
            </aside>

            {{-- Completion + review --}}
            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Progress</p>
                            <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Course completion</h3>
                        </div>
                        <x-badge :tone="$completion->is_eligible_for_certificate ? 'success' : 'warning'">{{ $completion->is_eligible_for_certificate ? 'Certificate eligible' : 'In progress' }}</x-badge>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Lessons</span><span class="font-bold text-mk-navy">{{ $completionChecklist['lessons']['percentage'] }}%</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Quizzes</span><span class="font-bold text-mk-navy">{{ $completionChecklist['quizzes']['percentage'] }}%</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Assignments</span><span class="font-bold text-mk-navy">{{ $completionChecklist['assignments']['percentage'] }}%</span></div>
                    </div>
                </section>

                <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Feedback</p>
                            <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Course review</h3>
                        </div>
                        @if ($studentReview)<x-status-badge :status="$studentReview->status" />@endif
                    </div>
                    @if ($studentReview)
                        <div class="mt-3 rounded-mk-md border border-slate-100 bg-slate-50 p-3">
                            <p class="text-sm font-black text-mk-navy">{{ $studentReview->rating }} / 5 stars</p>
                            @if ($studentReview->comment)<p class="mt-1 whitespace-pre-wrap break-words text-sm leading-6 text-slate-600">{{ $studentReview->comment }}</p>@endif
                        </div>
                    @elseif ($canReviewCourse)
                        <form method="POST" action="{{ route('student.course-reviews.store', $course) }}" class="mt-3 space-y-3">
                            @csrf
                            <x-form-field name="rating" label="Rating">
                                <select id="rating" name="rating" class="w-full rounded-mk-sm border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-mk-navy focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                                    <option value="">Choose rating</option>
                                    @for ($rating = 5; $rating >= 1; $rating--)
                                        <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ $rating }} star{{ $rating === 1 ? '' : 's' }}</option>
                                    @endfor
                                </select>
                            </x-form-field>
                            <x-form-field name="comment" label="Comment">
                                <textarea id="comment" name="comment" rows="3" class="w-full rounded-mk-sm border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="Share what helped you learn.">{{ old('comment') }}</textarea>
                            </x-form-field>
                            <x-button type="submit" size="sm" variant="secondary" class="w-full">Submit Review</x-button>
                        </form>
                    @else
                        <p class="mt-3 text-sm leading-6 text-slate-600">Complete your course access before leaving feedback.</p>
                    @endif
                </section>
            </div>

            {{-- Prev / Next --}}
            <section class="rounded-mk-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            @if ($previousLesson)
                            <x-button :href="route('student.courses.learn', ['course' => $course, 'lesson' => $previousLesson->id])" variant="secondary" class="w-full">Previous: {{ $previousLesson->title }}</x-button>
                        @endif
                    </div>
                    <div class="sm:text-right">
                        @if ($nextLesson)
                            <x-button :href="route('student.courses.learn', ['course' => $course, 'lesson' => $nextLesson->id])" variant="navy" class="w-full">Next: {{ $nextLesson->title }}</x-button>
                        @else
                            <x-button :href="route('student.my-courses')" variant="secondary" class="w-full">Back to My Courses</x-button>
                        @endif
                    </div>
                </div>
            </section>
        @else
            <x-empty-state
                icon="courses"
                title="No published lesson yet"
                description="Published lessons will appear here when this course is ready for students."
                action-label="Back to My Courses"
                :action-href="route('student.my-courses')"
            />
        @endif
    </div>
</x-course-player-layout>
