<x-dashboard-layout role="student" :title="$quiz->title" description="MK Scholars student quiz.">
    <section class="bg-slate-50 py-10">
        <div class="mk-container">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <x-badge tone="blue">{{ $quiz->lesson?->title }}</x-badge>
                    <h1 class="mt-3 break-words text-3xl font-extrabold tracking-normal text-mk-navy">{{ $quiz->title }}</h1>
                    @if ($quiz->description)
                        <p class="mt-3 max-w-3xl whitespace-pre-line text-sm leading-6 text-slate-600">{{ $quiz->description }}</p>
                    @endif
                </div>
                <x-button
                    :href="route('student.courses.learn', ['course' => $course, 'lesson' => $quiz->lesson_id])"
                    variant="secondary"
                >
                    Back to Lesson
                </x-button>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <main class="min-w-0">
                    @if ($resultAttempt)
                        <x-card highlighted>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <x-badge :tone="$resultAttempt->status === \App\Models\QuizAttempt::STATUS_PASSED ? 'green' : 'gray'">
                                        {{ ucfirst($resultAttempt->status) }}
                                    </x-badge>
                                    <h2 class="mt-4 text-3xl font-extrabold text-mk-navy">Quiz result</h2>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Your answers were graded automatically using the published correct options for this quiz.</p>
                                </div>
                                <x-button
                                    :href="route('student.courses.learn', ['course' => $course, 'lesson' => $quiz->lesson_id])"
                                    variant="secondary"
                                >
                                    Back to Lesson
                                </x-button>
                            </div>

                            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Score</p>
                                    <p class="mt-2 text-2xl font-extrabold text-mk-navy">{{ $resultAttempt->score }}/{{ $resultAttempt->total_points }}</p>
                                </div>
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Percentage</p>
                                    <p class="mt-2 text-2xl font-extrabold text-mk-navy">{{ $resultAttempt->percentage }}%</p>
                                </div>
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Correct</p>
                                    <p class="mt-2 text-2xl font-extrabold text-mk-navy">{{ $correctAnswerCount }}/{{ $quiz->questions->count() }}</p>
                                </div>
                            </div>

                            @if ($resultAttempt->answers->isNotEmpty())
                                <div class="mt-6 space-y-4">
                                    <h3 class="text-xl font-extrabold text-mk-navy">Answer review</h3>
                                    @foreach ($resultAttempt->answers as $answer)
                                        @php($correctOption = $answer->question?->options?->firstWhere('is_correct', true))
                                        <div class="rounded-lg border border-slate-100 bg-white p-4">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-mk-navy">{{ $loop->iteration }}. {{ $answer->question?->question_text ?? 'Quiz question' }}</p>
                                                    <p class="mt-2 text-sm leading-6 text-slate-600">Your answer: <span class="font-bold text-mk-navy">{{ $answer->option?->option_text ?? 'No answer selected' }}</span></p>
                                                    @if (! $answer->is_correct && $correctOption)
                                                        <p class="mt-1 text-sm leading-6 text-slate-600">Correct answer: <span class="font-bold text-emerald-700">{{ $correctOption->option_text }}</span></p>
                                                    @endif
                                                </div>
                                                <div class="shrink-0 text-left sm:text-right">
                                                    <x-badge :tone="$answer->is_correct ? 'green' : 'gray'">{{ $answer->is_correct ? 'Correct' : 'Incorrect' }}</x-badge>
                                                    <p class="mt-2 text-xs font-bold uppercase tracking-wide text-slate-500">{{ $answer->points_awarded }} pts</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </x-card>
                    @elseif ($attemptLimitReached)
                        <x-card>
                            <x-badge tone="gray">Attempts closed</x-badge>
                            <h2 class="mt-4 text-2xl font-extrabold text-mk-navy">Maximum attempts reached</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">You have used all available attempts for this quiz. Return to the lesson to continue learning.</p>
                            <x-button :href="route('student.courses.learn', ['course' => $course, 'lesson' => $quiz->lesson_id])" variant="secondary" class="mt-5">Back to Lesson</x-button>
                        </x-card>
                    @elseif ($quiz->questions->isEmpty())
                        <x-card>
                            <x-badge tone="gray">Not ready</x-badge>
                            <h2 class="mt-4 text-2xl font-extrabold text-mk-navy">No published questions yet</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">This quiz will be available once questions are published.</p>
                        </x-card>
                    @else
                        <x-card highlighted class="mb-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Quiz overview</p>
                                    <h2 class="mt-3 text-2xl font-extrabold text-mk-navy">Answer every question before submitting</h2>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">Choose the best option for each question. Your score is calculated immediately after submission.</p>
                                </div>
                                <x-badge tone="gold">Pass: {{ $quiz->passing_score }}%</x-badge>
                            </div>
                        </x-card>

                        <form method="POST" action="{{ route('student.quizzes.submit', $quiz) }}" class="space-y-5">
                            @csrf

                            @if ($errors->any())
                                <div class="rounded-lg border border-red-100 bg-red-50 p-4 text-sm font-semibold text-red-700">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            @foreach ($quiz->questions as $question)
                                <x-card>
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-badge tone="gray">Question {{ $loop->iteration }}</x-badge>
                                                <x-badge tone="blue">{{ str_replace('_', ' ', $question->question_type) }}</x-badge>
                                            </div>
                                            <h2 class="mt-3 break-words text-xl font-extrabold text-mk-navy">{{ $question->question_text }}</h2>
                                            <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Required answer</p>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-mk-goldSoft px-3 py-1 text-sm font-bold text-mk-navy">{{ $question->points }} pts</span>
                                    </div>

                                    <div class="mt-5 space-y-3">
                                        @forelse ($question->options as $option)
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-100 bg-white p-4 transition hover:border-mk-gold hover:bg-mk-goldSoft/40">
                                                <input
                                                    type="radio"
                                                    name="answers[{{ $question->id }}]"
                                                    value="{{ $option->id }}"
                                                    @checked((string) old('answers.'.$question->id) === (string) $option->id)
                                                    required
                                                    class="mt-1 text-mk-gold focus:ring-mk-gold"
                                                >
                                                <span class="break-words text-sm leading-6 text-slate-700">{{ $option->option_text }}</span>
                                            </label>
                                        @empty
                                            <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No answer options have been published for this question.</p>
                                        @endforelse
                                    </div>
                                </x-card>
                            @endforeach

                            <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm font-semibold text-slate-600">Ready? Submit once you have answered every question.</p>
                                <x-button type="submit" size="lg" class="w-full sm:w-auto">Submit Quiz</x-button>
                            </div>
                        </form>
                    @endif
                </main>

                <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
                    <x-card>
                        <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Quiz details</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-slate-500">Passing score</dt>
                                <dd class="font-bold text-mk-navy">{{ $quiz->passing_score }}%</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-slate-500">Questions</dt>
                                <dd class="font-bold text-mk-navy">{{ $quiz->questions->count() }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-slate-500">Total points</dt>
                                <dd class="font-bold text-mk-navy">{{ $quiz->questions->sum('points') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-slate-500">Attempts</dt>
                                <dd class="font-bold text-mk-navy">
                                    {{ $attemptCount }}{{ $quiz->max_attempts ? ' / '.$quiz->max_attempts : ' used' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-slate-500">Time limit</dt>
                                <dd class="font-bold text-mk-navy">{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : 'None' }}</dd>
                            </div>
                        </dl>
                    </x-card>

                    <x-card highlighted>
                        <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Course</p>
                        <h3 class="mt-3 break-words text-xl font-extrabold text-mk-navy">{{ $course->title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Return to the lesson after submitting your answers.</p>
                    </x-card>
                </aside>
            </div>
        </div>
    </section>
</x-dashboard-layout>

