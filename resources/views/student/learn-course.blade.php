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
        $latestQuizStatusLabel = $latestQuizAttempt
            ? str_replace('_', ' ', $latestQuizAttempt->status)
            : null;
        $latestFinalTestAttempt = $finalTest?->attempts?->first();
        $latestFinalTestStatusLabel = $latestFinalTestAttempt
            ? str_replace('_', ' ', $latestFinalTestAttempt->status)
            : null;
        $quizPassed = $latestQuizAttempt?->status === \App\Models\QuizAttempt::STATUS_PASSED;
        $firstAssignment = $currentAssignments->first();
        $firstAssignmentSubmission = $firstAssignment?->submissions?->first();
        $moduleTitle = $currentLesson?->module?->title ?? 'Learning path';
        $renderedLessonContent = $currentLesson ? \App\Support\CourseContentRenderer::render($currentLesson->content) : '';
        $lessonCompletedLabel = match ($currentLesson?->lesson_type) {
            'video' => 'Video Completed',
            'text', 'reading' => 'Reading Completed',
            default => 'Lesson Completed',
        };
        $lessonIncompleteLabel = match ($currentLesson?->lesson_type) {
            'video' => 'Mark Video as Completed',
            'text', 'reading' => 'Mark Reading as Completed',
            default => 'Mark Lesson as Completed',
        };
        $quizCompletedAttempts = $currentQuiz?->attempts?->count() ?? 0;
        $quizAttemptsExhausted = $currentQuiz && $currentQuiz->max_attempts !== null && $quizCompletedAttempts >= $currentQuiz->max_attempts;
        $quizActionLabel = match (true) {
            ! $latestQuizAttempt => 'Start Quiz',
            in_array($latestQuizAttempt->status, [\App\Models\QuizAttempt::STATUS_PASSED, \App\Models\QuizAttempt::STATUS_SUBMITTED], true) => 'Quiz Completed',
            $latestQuizAttempt->status === \App\Models\QuizAttempt::STATUS_FAILED && ! $quizAttemptsExhausted => 'Retry Quiz',
            $latestQuizAttempt->status === \App\Models\QuizAttempt::STATUS_FAILED && $quizAttemptsExhausted => 'Attempts Exhausted',
            default => 'Start Quiz',
        };
        $finalTestCompletedAttempts = $finalTest?->attempts?->count() ?? 0;
        $finalTestAttemptsExhausted = $finalTest && $finalTest->max_attempts !== null && $finalTestCompletedAttempts >= $finalTest->max_attempts;
        $finalTestActionLabel = match (true) {
            ! $latestFinalTestAttempt => 'Start Test',
            in_array($latestFinalTestAttempt->status, [\App\Models\QuizAttempt::STATUS_PASSED, \App\Models\QuizAttempt::STATUS_SUBMITTED], true) => 'Final Test Completed',
            $latestFinalTestAttempt->status === \App\Models\QuizAttempt::STATUS_FAILED && ! $finalTestAttemptsExhausted => 'Retry Test',
            $latestFinalTestAttempt->status === \App\Models\QuizAttempt::STATUS_FAILED && $finalTestAttemptsExhausted => 'Test Attempts Exhausted',
            default => 'Start Test',
        };
        $assignmentActionLabel = match ($firstAssignmentSubmission?->status) {
            \App\Models\AssignmentSubmission::STATUS_GRADED => 'Assignment Completed',
            \App\Models\AssignmentSubmission::STATUS_SUBMITTED => 'Assignment Submitted',
            \App\Models\AssignmentSubmission::STATUS_RESUBMISSION_REQUIRED => 'Submit Assignment',
            default => 'Submit Assignment',
        };
        $completedLessonIdCollection = collect($completedLessonIds);
        $videoLessons = $allLessons->filter(fn ($lesson) => $lesson->lesson_type === 'video' || filled($lesson->video_url));
        $readingLessons = $allLessons->filter(fn ($lesson) => in_array($lesson->lesson_type, ['text', 'reading'], true));
        $allLessonQuizzes = $allLessons->flatMap(fn ($lesson) => $lesson->quizzes ?? collect());
        $allAssignments = $allLessons->flatMap(fn ($lesson) => $lesson->assignments ?? collect());
        $completedQuizCount = $allLessonQuizzes->filter(fn ($quiz) => in_array($quiz->attempts?->first()?->status, [\App\Models\QuizAttempt::STATUS_PASSED, \App\Models\QuizAttempt::STATUS_SUBMITTED], true))->count();
        $submittedAssignmentCount = $allAssignments->filter(fn ($assignment) => in_array($assignment->submissions?->first()?->status, [\App\Models\AssignmentSubmission::STATUS_SUBMITTED, \App\Models\AssignmentSubmission::STATUS_GRADED], true))->count();
        $finalTestSummaryLabel = ! $finalTest
            ? 'Not required'
            : (in_array($latestFinalTestAttempt?->status, [\App\Models\QuizAttempt::STATUS_PASSED, \App\Models\QuizAttempt::STATUS_SUBMITTED], true) ? 'Final Test Completed' : 'Final Test Required');
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
                            {{ $lessonCompletedLabel }}
                        </span>
                    @else
                        <form method="POST" action="{{ route('student.lessons.complete', ['course' => $course, 'lesson' => $currentLesson]) }}" class="shrink-0">
                            @csrf
                            <x-button type="submit" size="sm">{{ $lessonIncompleteLabel }}</x-button>
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
                                @if ($latestQuizAttempt)· Latest: {{ $latestQuizAttempt->percentage }}% {{ $latestQuizStatusLabel }}@endif
                            </p>
                        </div>
                        @if ($latestQuizAttempt)<x-status-badge :status="$latestQuizAttempt->status" />@else<x-badge tone="blue">Ready</x-badge>@endif
                    </div>
                    @if ($quizActionLabel === 'Attempts Exhausted')
                        <span class="mt-4 inline-flex rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600">Attempts Exhausted</span>
                    @else
                        <x-button :href="route('student.quizzes.show', $currentQuiz)" size="sm" :variant="$quizActionLabel === 'Quiz Completed' ? 'secondary' : 'primary'" class="mt-4">{{ $quizActionLabel }}</x-button>
                    @endif
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
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-badge tone="gray">Document</x-badge>
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($firstAssignment->instruction_file_path) }}" class="mk-focus inline-flex rounded-sm text-sm font-bold text-mk-navy underline decoration-mk-gold underline-offset-4" target="_blank" rel="noopener noreferrer">View assignment document</a>
                        </div>
                    @endif
                    <x-button :href="route('student.assignments.show', $firstAssignment)" size="sm" :variant="$firstAssignmentSubmission ? 'secondary' : 'primary'" class="mt-4">{{ $assignmentActionLabel }}</x-button>
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
                                @if ($activity->hasUploadedResource() && $activity->isPdfResource())
                                    <div class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-white" data-testid="lesson-pdf-viewer">
                                        <iframe
                                            src="{{ route('student.lesson-materials.view', $activity) }}"
                                            title="{{ $activity->title }} PDF notes"
                                            class="h-[70vh] min-h-[420px] w-full"
                                        ></iframe>
                                    </div>
                                @elseif ($activity->hasUploadedResource() && $activity->isImageResource() && \Illuminate\Support\Facades\Storage::disk($activity->resourceDisk())->exists($activity->resource_path))
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk($activity->resourceDisk())->url($activity->resource_path) }}" alt="{{ $activity->title }}" class="mt-3 h-auto max-w-full rounded-lg border border-slate-200 shadow-sm">
                                @elseif ($activity->hasUploadedResource())
                                    <div class="mt-3 rounded-lg border border-slate-200 bg-white p-3 text-sm font-semibold text-slate-600">
                                        Material uploaded for this lesson. Ask your instructor if it should be viewable in the browser.
                                    </div>
                                @elseif ($activity->resource_url)
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
                    @if ($errors->has('live_class'))
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900">{{ $errors->first('live_class') }}</div>
                    @endif
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($currentLiveClasses as $liveClass)
                            <div class="rounded-mk-md border border-slate-100 bg-slate-50 p-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge :tone="$liveClass->displayStatusTone()">{{ $liveClass->displayStatus() }}</x-badge>
                                    <x-badge tone="blue">{{ str_replace('_', ' ', $liveClass->platform) }}</x-badge>
                                </div>
                                <p class="mt-2 text-sm font-bold text-mk-navy">{{ $liveClass->title }}</p>
                                @if ($liveClass->description)
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $liveClass->description }}</p>
                                @endif
                                <p class="mt-2 text-xs font-semibold text-slate-500">
                                    {{ $liveClass->starts_at->format('M j, Y g:i A') }}
                                    @if ($liveClass->ends_at)
                                        - {{ $liveClass->ends_at->format('M j, Y g:i A') }}
                                    @endif
                                </p>
                                @if ($liveClass->canJoin())
                                    <form method="POST" action="{{ route('student.live-classes.join', $liveClass) }}" class="mt-3">
                                        @csrf
                                        <x-button type="submit" size="sm" class="w-full">Join Class</x-button>
                                    </form>
                                @elseif ($liveClass->canWatchRecording())
                                    <x-button :href="route('student.live-classes.recording', $liveClass)" size="sm" variant="secondary" class="mt-3 w-full">Watch Recording</x-button>
                                @elseif ($liveClass->status === \App\Models\LiveClass::STATUS_CANCELLED)
                                    <div class="mt-3 rounded-lg bg-white p-3 text-center text-sm font-semibold text-slate-500">Cancelled</div>
                                @elseif ($liveClass->isEnded())
                                    <div class="mt-3 rounded-lg bg-white p-3 text-center">
                                        <p class="text-sm font-bold text-slate-700">Class Ended</p>
                                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Recording Not Available</p>
                                    </div>
                                @else
                                    <div class="mt-3 rounded-lg bg-white p-3 text-center text-sm font-semibold text-slate-500">Class starts soon</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
            </aside>

            {{-- Completion + review --}}
            @if ($finalTest)
                <section class="rounded-mk-lg border border-mk-gold/50 bg-white p-5 shadow-sm" data-testid="learning-final-test-card">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Final Test</p>
                            <h3 class="mt-1 break-words text-xl font-black text-mk-navy">{{ $finalTest->title }}</h3>
                            @if ($finalTest->description)
                                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $finalTest->description }}</p>
                            @endif
                            <p class="mt-3 text-xs font-semibold text-slate-500">
                                {{ $finalTest->questions_count ?? 0 }} questions
                                - Passing score: {{ $finalTest->passing_score }}%
                                - Time limit: {{ $finalTest->time_limit_minutes ? $finalTest->time_limit_minutes.' min' : 'None' }}
                                - Attempts: {{ $finalTest->max_attempts ?: 'Unlimited' }}
                                @if ($latestFinalTestAttempt) - Latest: {{ $latestFinalTestAttempt->percentage }}% {{ $latestFinalTestStatusLabel }}@endif
                            </p>
                        </div>
                        @if ($latestFinalTestAttempt)<x-status-badge :status="$latestFinalTestAttempt->status" />@else<x-badge tone="blue">Ready</x-badge>@endif
                    </div>
                    @if ($finalTestActionLabel === 'Test Attempts Exhausted')
                        <span class="mt-4 inline-flex rounded-md bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600">Test Attempts Exhausted</span>
                    @else
                        <x-button :href="route('student.quizzes.show', $finalTest)" size="sm" :variant="$finalTestActionLabel === 'Final Test Completed' ? 'secondary' : 'primary'" class="mt-4">{{ $finalTestActionLabel }}</x-button>
                    @endif
                </section>
            @endif

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-mk-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Completion</p>
                            <h3 class="mt-1 text-lg font-extrabold text-mk-navy">Course completion</h3>
                        </div>
                        <x-badge :tone="$completion->is_eligible_for_certificate ? 'success' : 'warning'">
                            {{ $course->offersCertificate() && $completion->is_eligible_for_certificate ? 'Certificate eligible' : ($completion->is_eligible_for_certificate ? 'Completed' : 'In progress') }}
                        </x-badge>
                    </div>
                    <div class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Videos completed</span><span class="font-bold text-mk-navy">{{ $videoLessons->filter(fn ($lesson) => $completedLessonIdCollection->contains($lesson->id))->count() }} / {{ $videoLessons->count() }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Reading lessons completed</span><span class="font-bold text-mk-navy">{{ $readingLessons->filter(fn ($lesson) => $completedLessonIdCollection->contains($lesson->id))->count() }} / {{ $readingLessons->count() }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Quizzes completed</span><span class="font-bold text-mk-navy">{{ $completedQuizCount }} / {{ $allLessonQuizzes->count() }}</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Assignments submitted/completed</span><span class="font-bold text-mk-navy">{{ $submittedAssignmentCount }} / {{ $allAssignments->count() }}</span></div>
                        @if ($completionChecklist['final_test']['required'] ?? false)
                            <div class="flex justify-between gap-3"><span class="text-slate-500">Final Test</span><span class="font-bold text-mk-navy">{{ $finalTestSummaryLabel }}</span></div>
                        @endif
                        <div class="flex justify-between gap-3 border-t border-slate-100 pt-2"><span class="text-slate-500">Overall progress</span><span class="font-bold text-mk-navy">{{ $progress }}%</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">Course status</span><span class="font-bold text-mk-navy">{{ $completion->is_eligible_for_certificate ? 'Completed' : 'In Progress' }}</span></div>
                        @if (! $course->offersCertificate())
                            <div class="flex justify-between gap-3"><span class="text-slate-500">Certificate</span><span class="font-bold text-mk-navy">Not offered</span></div>
                        @endif
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

