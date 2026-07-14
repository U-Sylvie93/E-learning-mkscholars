@php
    $isFinalTest = $quiz->isFinalTest();
    $assessmentName = $isFinalTest ? 'Final Test' : 'Quiz';
    $assessmentNoun = $isFinalTest ? 'test' : 'quiz';
@endphp

<x-dashboard-layout role="student" :title="$quiz->title" :description="'MK Scholars '.$assessmentNoun.' result.'">
    <section class="bg-slate-50 py-10">
        <div class="mk-container max-w-5xl">
            @if (session('status'))
                <div class="mb-5 rounded-mk-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif

            <div class="rounded-mk-lg border border-slate-200 bg-white p-6 shadow-sm sm:p-8" data-testid="quiz-result">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <x-badge :tone="$attempt->status === \App\Models\QuizAttempt::STATUS_PASSED ? 'green' : 'gray'">
                            {{ $attempt->status === \App\Models\QuizAttempt::STATUS_PASSED ? 'Passed' : 'Not passed' }}
                        </x-badge>
                        <h1 class="mt-4 text-3xl font-black tracking-normal text-mk-navy">{{ $assessmentName }} result</h1>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Objective answers were graded from published correct options. Text answers are saved for review and are not auto-scored.</p>
                    </div>
                    <x-button :href="$quiz->lesson_id ? route('student.courses.learn', ['course' => $course, 'lesson' => $quiz->lesson_id]) : route('student.courses.learn', $course)" variant="secondary">Back to learning</x-button>
                </div>

                <div class="mt-7 grid gap-4 sm:grid-cols-4">
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Score</p>
                        <p class="mt-2 text-2xl font-black text-mk-navy">{{ $attempt->score }}/{{ $attempt->total_points }}</p>
                    </div>
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Percentage</p>
                        <p class="mt-2 text-2xl font-black text-mk-navy">{{ $attempt->percentage }}%</p>
                    </div>
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Correct</p>
                        <p class="mt-2 text-2xl font-black text-mk-navy">{{ $correctAnswerCount }}/{{ $quiz->questions->count() }}</p>
                    </div>
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Pass Mark</p>
                        <p class="mt-2 text-2xl font-black text-mk-navy">{{ $quiz->passing_score }}%</p>
                    </div>
                </div>

                @if (! $attemptLimitReached)
                    <form method="POST" action="{{ route('student.quizzes.start', $quiz) }}" class="mt-6">
                        @csrf
                        <x-button type="submit" variant="secondary">Retake {{ $isFinalTest ? 'Test' : 'Quiz' }}</x-button>
                    </form>
                @endif

                @if ($attempt->answers->isNotEmpty())
                    <div class="mt-8 space-y-4">
                        <h2 class="text-xl font-black text-mk-navy">Answer review</h2>
                        @foreach ($attempt->answers as $answer)
                            @php
                                $selectedOptionIds = collect($answer->selected_option_ids ?? ($answer->quiz_option_id ? [$answer->quiz_option_id] : []))
                                    ->map(fn ($optionId) => (int) $optionId)
                                    ->all();
                                $selectedOptions = $answer->question?->options?->whereIn('id', $selectedOptionIds)->pluck('option_text')->values() ?? collect();
                                $correctOptions = $answer->question?->options?->where('is_correct', true)->pluck('option_text')->values() ?? collect();
                                $answerText = $answer->answer_text;
                                $displayedAnswer = $answer->question?->acceptsTextAnswer()
                                    ? ($answerText ?: 'No answer entered')
                                    : ($selectedOptions->isNotEmpty() ? $selectedOptions->join(', ') : 'No answer selected');
                            @endphp
                            <article class="rounded-mk-md border border-slate-200 bg-white p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-black text-mk-navy">{{ $loop->iteration }}. {{ $answer->question?->question_text ?? $assessmentName.' question' }}</p>
                                        <p class="mt-2 break-words text-sm leading-6 text-slate-600">Your answer: <span class="font-bold text-mk-navy">{{ $displayedAnswer }}</span></p>
                                        @if (! $answer->question?->acceptsTextAnswer() && ! $answer->is_correct && $correctOptions->isNotEmpty())
                                            <p class="mt-1 break-words text-sm leading-6 text-slate-600">Correct answer: <span class="font-bold text-emerald-700">{{ $correctOptions->join(', ') }}</span></p>
                                        @endif
                                    </div>
                                    <div class="shrink-0">
                                        <x-badge :tone="$answer->question?->acceptsTextAnswer() ? 'gray' : ($answer->is_correct ? 'green' : 'gray')">{{ $answer->question?->acceptsTextAnswer() ? 'Submitted' : ($answer->is_correct ? 'Correct' : 'Incorrect') }}</x-badge>
                                        <p class="mt-2 text-xs font-black uppercase tracking-wide text-slate-500">{{ $answer->points_awarded }} pts</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-dashboard-layout>
