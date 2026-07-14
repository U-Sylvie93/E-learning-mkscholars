@php
    $isFinalTest = $quiz->isFinalTest();
    $assessmentNoun = $isFinalTest ? 'test' : 'quiz';
@endphp

<x-quiz-focus-shell :title="$quiz->title" :description="'MK Scholars active '.$assessmentNoun.' mode.'">
    @php
        $isFinalQuestion = $questionIndex >= $questions->count() - 1;
        $previousIndex = max(0, $questionIndex - 1);
        $savedOptionIds = collect($savedAnswer?->selected_option_ids ?? ($savedAnswer?->quiz_option_id ? [$savedAnswer->quiz_option_id] : []))
            ->map(fn ($optionId) => (int) $optionId)
            ->all();
        $isMultipleChoice = $question->acceptsMultipleOptions();
        $isTextAnswer = $question->acceptsTextAnswer();
        $isLongTextAnswer = $question->acceptsLongTextAnswer();
        $selectedOptionIds = $isMultipleChoice
            ? collect(old('option_ids', $savedOptionIds))->map(fn ($optionId) => (int) $optionId)->all()
            : [(int) old('option_id', $savedAnswer?->quiz_option_id ?? 0)];
        $savedTextAnswer = old('answer_text', $savedAnswer?->answer_text);
    @endphp

    <div class="mx-auto flex min-h-[calc(100vh-3rem)] w-full max-w-4xl items-center">
        <section class="w-full overflow-hidden rounded-mk-lg bg-white shadow-2xl" data-testid="quiz-exam-mode">
            <header class="border-b border-slate-200 bg-white p-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Focus Mode</p>
                        <h1 class="mt-2 break-words text-2xl font-black text-mk-navy sm:text-3xl">{{ $quiz->title }}</h1>
                        <p class="mt-1 text-sm font-semibold text-slate-500">{{ $course->title }} · Question {{ $questionIndex + 1 }} of {{ $questions->count() }}</p>
                    </div>
                    <div class="rounded-mk-md border border-mk-gold/40 bg-mk-goldSoft px-4 py-3 text-center">
                        <p class="text-[11px] font-black uppercase tracking-wide text-mk-navy">Time Remaining</p>
                        <p class="mt-1 text-xl font-black text-mk-navy" data-quiz-timer data-seconds="{{ $secondsRemaining ?? '' }}">{{ $secondsRemaining === null ? 'No limit' : gmdate('H:i:s', $secondsRemaining) }}</p>
                    </div>
                </div>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-mk-gold" style="width: {{ (int) round((($questionIndex + 1) / max($questions->count(), 1)) * 100) }}%"></div>
                </div>
            </header>

            @if (session('status'))
                <div class="border-b border-emerald-100 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="border-b border-red-100 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('student.quizzes.answer', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => $questionIndex]) }}" class="select-none p-5 sm:p-8" data-quiz-guard>
                @csrf
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge tone="gray">Question {{ $questionIndex + 1 }}</x-badge>
                            <x-badge tone="blue">{{ str_replace('_', ' ', $question->question_type) }}</x-badge>
                        </div>
                        <h2 class="mt-4 break-words text-2xl font-black leading-tight text-mk-navy">{{ $question->question_text }}</h2>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-sm font-black text-mk-navy">{{ $question->points }} pts</span>
                </div>

                @if ($isTextAnswer)
                    <div class="mt-7">
                        <label class="block text-sm font-bold text-mk-navy" for="answer_text">
                            {{ $isLongTextAnswer ? 'Long Text Answer' : 'Short Text Answer' }}
                        </label>
                        @if ($isLongTextAnswer)
                            <textarea id="answer_text" name="answer_text" rows="7" required class="mt-3 w-full rounded-mk-md border border-slate-200 px-4 py-3 text-sm leading-6 focus:border-mk-gold focus:ring-mk-gold">{{ $savedTextAnswer }}</textarea>
                        @else
                            <input id="answer_text" name="answer_text" value="{{ $savedTextAnswer }}" required class="mt-3 w-full rounded-mk-md border border-slate-200 px-4 py-3 text-sm focus:border-mk-gold focus:ring-mk-gold">
                        @endif
                    </div>
                @else
                    <fieldset class="mt-7 space-y-3">
                        <legend class="sr-only">{{ $isMultipleChoice ? 'Choose all correct answers' : 'Choose one answer' }}</legend>
                        @foreach ($question->options as $option)
                            <label class="flex cursor-pointer items-start gap-3 rounded-mk-md border border-slate-200 bg-white p-4 transition hover:border-mk-gold hover:bg-mk-goldSoft/40">
                                <input
                                    type="{{ $isMultipleChoice ? 'checkbox' : 'radio' }}"
                                    name="{{ $isMultipleChoice ? 'option_ids[]' : 'option_id' }}"
                                    value="{{ $option->id }}"
                                    @checked(in_array($option->id, $selectedOptionIds, true))
                                    @if (! $isMultipleChoice) required @endif
                                    class="mt-1 text-mk-gold focus:ring-mk-gold"
                                >
                                <span class="break-words text-sm font-semibold leading-6 text-slate-700">{{ $option->option_text }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        @if ($questionIndex > 0)
                            <x-button :href="route('student.quizzes.question', ['quiz' => $quiz, 'attempt' => $attempt, 'questionIndex' => $previousIndex])" variant="secondary">Previous</x-button>
                        @endif
                    </div>
                    <x-button type="submit" size="lg" name="finish" value="{{ $isFinalQuestion ? '1' : '0' }}">
                        {{ $isFinalQuestion ? ($isFinalTest ? 'Finish Test' : 'Finish Quiz') : 'Save and Next' }}
                    </x-button>
                </div>
            </form>
        </section>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-quiz-guard]');
            const timer = document.querySelector('[data-quiz-timer]');

            if (root) {
                root.addEventListener('contextmenu', (event) => event.preventDefault());
                root.addEventListener('dragstart', (event) => event.preventDefault());
                document.addEventListener('keydown', (event) => {
                    const key = event.key.toLowerCase();
                    if ((event.ctrlKey || event.metaKey) && ['c', 'u', 's', 'p'].includes(key)) {
                        event.preventDefault();
                    }
                });
            }

            if (! timer || timer.dataset.seconds === '') {
                return;
            }

            let remaining = Number(timer.dataset.seconds);
            const render = () => {
                const hours = String(Math.floor(remaining / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((remaining % 3600) / 60)).padStart(2, '0');
                const seconds = String(remaining % 60).padStart(2, '0');
                timer.textContent = `${hours}:${minutes}:${seconds}`;
            };

            render();
            window.setInterval(() => {
                remaining = Math.max(0, remaining - 1);
                render();
            }, 1000);
        })();
    </script>
</x-quiz-focus-shell>

