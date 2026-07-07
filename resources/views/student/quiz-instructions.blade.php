<x-dashboard-layout role="student" :title="$quiz->title" description="MK Scholars guided quiz instructions.">
    <section class="bg-slate-50 py-10">
        <div class="mk-container max-w-5xl">
            @if (session('status'))
                <div class="mb-5 rounded-mk-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-5 rounded-mk-md border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
            @endif

            <div class="rounded-mk-lg border border-slate-200 bg-white p-6 shadow-sm sm:p-8" data-testid="quiz-instructions">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge tone="blue">{{ $course->title }}</x-badge>
                            @if ($quiz->lesson?->module)
                                <x-badge tone="gray">{{ $quiz->lesson->module->title }}</x-badge>
                            @endif
                        </div>
                        <h1 class="mt-4 break-words text-3xl font-black tracking-normal text-mk-navy sm:text-4xl">{{ $quiz->title }}</h1>
                        @if ($quiz->description)
                            <div class="mk-rich-content mt-4">
                                {!! \App\Support\CourseContentRenderer::render($quiz->description) !!}
                            </div>
                        @else
                            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-600">Read the rules, then start when you are ready.</p>
                        @endif
                        <p class="mt-4 max-w-3xl text-sm font-semibold leading-7 text-slate-700">Timer starts only after you press Start Quiz.</p>
                    </div>
                    <x-button :href="route('student.courses.learn', ['course' => $course, 'lesson' => $quiz->lesson_id])" variant="secondary">Back to Lesson</x-button>
                </div>

                <dl class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Questions</dt>
                        <dd class="mt-1 text-2xl font-black text-mk-navy">{{ $quiz->questions->count() }}</dd>
                    </div>
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Passing Score</dt>
                        <dd class="mt-1 text-2xl font-black text-mk-navy">{{ $quiz->passing_score }}%</dd>
                    </div>
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Attempts</dt>
                        <dd class="mt-1 text-2xl font-black text-mk-navy">{{ $attemptCount }}{{ $quiz->max_attempts ? ' / '.$quiz->max_attempts : '' }}</dd>
                    </div>
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <dt class="text-xs font-black uppercase tracking-wide text-slate-500">Time Limit</dt>
                        <dd class="mt-1 text-2xl font-black text-mk-navy">{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.'m' : 'None' }}</dd>
                    </div>
                </dl>

                <div class="mt-8 rounded-mk-md border border-mk-gold/40 bg-mk-goldSoft/50 p-5">
                    <h2 class="text-lg font-black text-mk-navy">Important rules</h2>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-700">
                        <li>Timer starts only after you press Start Quiz.</li>
                        <li>Each answer is saved when you click Save and Next.</li>
                        <li>Refreshes do not reset the timer.</li>
                        <li>You can review your answers after finishing the quiz.</li>
                    </ul>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-semibold text-slate-600">
                        @if ($activeAttempt)
                            You have an active attempt in progress.
                        @elseif ($attemptLimitReached)
                            You have reached the attempt limit for this quiz.
                        @else
                            Start only when you are ready to focus.
                        @endif
                    </p>
                    @if ($attemptLimitReached && ! $activeAttempt)
                        <x-button :href="route('student.courses.learn', ['course' => $course, 'lesson' => $quiz->lesson_id])" variant="secondary">Return to Lesson</x-button>
                    @else
                        <form method="POST" action="{{ route('student.quizzes.start', $quiz) }}">
                            @csrf
                            <x-button type="submit" size="lg">{{ $activeAttempt ? 'Resume Quiz' : 'Start Quiz' }}</x-button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-dashboard-layout>
