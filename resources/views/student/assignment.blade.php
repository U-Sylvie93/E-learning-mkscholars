<x-dashboard-layout role="student" :title="$assignment->title" description="MK Scholars student assignment.">
    @php
        $answerByQuestionId = $submission?->questionAnswers?->keyBy('assignment_question_id') ?? collect();
        $instructionFileExists = $assignment->instruction_file_path
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($assignment->instruction_file_path);
        $instructionFileUrl = $instructionFileExists
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($assignment->instruction_file_path)
            : null;
        $submissionFileExists = $submission?->file_path
            && \Illuminate\Support\Facades\Storage::disk('public')->exists($submission->file_path);
        $submissionFileUrl = $submissionFileExists
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($submission->file_path)
            : null;
    @endphp

    <section class="bg-slate-50 py-10">
        <div class="mk-container">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <x-badge tone="blue">{{ $assignment->lesson?->title }}</x-badge>
                    <h1 class="mt-3 break-words text-3xl font-extrabold tracking-normal text-mk-navy">{{ $assignment->title }}</h1>
                    <p class="mt-3 max-w-3xl whitespace-pre-line text-sm leading-6 text-slate-600">{{ $assignment->instructions }}</p>
                    @if ($instructionFileUrl)
                        <a href="{{ $instructionFileUrl }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex rounded-md border border-mk-gold/40 bg-white px-4 py-2 text-sm font-bold text-mk-navy transition hover:bg-mk-goldSoft">
                            Download assignment document
                        </a>
                    @endif
                </div>
                <x-button
                    :href="route('student.courses.learn', ['course' => $course, 'lesson' => $assignment->lesson_id])"
                    variant="secondary"
                >
                    Back to Lesson
                </x-button>
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <main>
                    @if ($errors->any())
                        <div class="mb-5 rounded-lg border border-red-100 bg-red-50 p-4 text-sm font-semibold text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if ($submission)
                        <x-card highlighted class="mb-6">
                            <x-badge :tone="$submission->status === 'graded' ? 'green' : 'gold'">
                                {{ str_replace('_', ' ', $submission->status) }}
                            </x-badge>
                            <h2 class="mt-4 text-2xl font-extrabold text-mk-navy">Submission status</h2>
                            <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                                <div class="rounded-lg bg-white p-4">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Submitted</dt>
                                    <dd class="mt-2 text-sm font-bold text-mk-navy">{{ $submission->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</dd>
                                </div>
                                <div class="rounded-lg bg-white p-4">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Score</dt>
                                    <dd class="mt-2 text-sm font-bold text-mk-navy">{{ $submission->score !== null ? $submission->score.' / '.$assignment->max_score : 'Not graded' }}</dd>
                                </div>
                                <div class="rounded-lg bg-white p-4">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Graded</dt>
                                    <dd class="mt-2 text-sm font-bold text-mk-navy">{{ $submission->graded_at?->format('M j, Y') ?? 'Pending' }}</dd>
                                </div>
                            </dl>

                            @if ($submissionFileUrl)
                                <div class="mt-5 rounded-lg bg-white p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Submitted file</p>
                                    <a href="{{ $submissionFileUrl }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-sm font-bold text-mk-navy underline decoration-mk-gold underline-offset-4">
                                        {{ basename($submission->file_path) }}
                                    </a>
                                </div>
                            @endif

                            @if ($submission->questionAnswers->isNotEmpty())
                                <div class="mt-5 rounded-lg bg-white p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Submitted answers</p>
                                    <div class="mt-4 space-y-4">
                                        @foreach ($submission->questionAnswers as $answer)
                                            <div class="rounded-lg bg-slate-50 p-4">
                                                <p class="text-sm font-bold text-mk-navy">{{ $answer->question?->question_text ?? 'Assignment question' }}</p>
                                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $answer->answer ?: 'No answer provided.' }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($submission->feedback)
                                <div class="mt-5 rounded-lg bg-white p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Feedback</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $submission->feedback }}</p>
                                </div>
                            @endif
                        </x-card>
                    @endif

                    @if ($canSubmit)
                        <form method="POST" action="{{ route('student.assignments.submit', $assignment) }}" enctype="multipart/form-data">
                            @csrf
                            <x-card>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge tone="gray">{{ $assignment->submission_type }}</x-badge>
                                    <x-badge tone="gold">Max {{ $assignment->max_score }} pts</x-badge>
                                    @if ($assignment->questions->isNotEmpty())
                                        <x-badge tone="blue">{{ $assignment->questions->count() }} questions</x-badge>
                                    @endif
                                </div>

                                @if ($assignment->questions->isNotEmpty())
                                    <div class="mt-6 space-y-5">
                                        <h2 class="text-xl font-extrabold text-mk-navy">Assignment questions</h2>
                                        @foreach ($assignment->questions as $question)
                                            @php($currentAnswer = old('question_answers.'.$question->id, $answerByQuestionId->get($question->id)?->answer))
                                            <label class="block rounded-lg border border-slate-100 bg-slate-50 p-4">
                                                <span class="block text-sm font-bold text-mk-navy">
                                                    {{ $question->question_text }}
                                                    @if ($question->is_required)
                                                        <span class="text-red-600">*</span>
                                                    @endif
                                                </span>
                                                @if ($question->points !== null)
                                                    <span class="mt-1 block text-xs font-semibold text-slate-500">{{ $question->points }} pts</span>
                                                @endif
                                                @if ($question->question_type === \App\Models\AssignmentQuestion::TYPE_TEXT)
                                                    <input name="question_answers[{{ $question->id }}]" value="{{ $currentAnswer }}" @required($question->is_required) class="mt-3 w-full rounded-md border border-slate-200 px-4 py-3 text-sm focus:border-mk-gold focus:ring-mk-gold">
                                                @else
                                                    <textarea name="question_answers[{{ $question->id }}]" rows="5" @required($question->is_required) class="mt-3 w-full rounded-md border border-slate-200 px-4 py-3 text-sm focus:border-mk-gold focus:ring-mk-gold">{{ $currentAnswer }}</textarea>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>
                                @endif

                                @if (in_array($assignment->submission_type, ['text', 'mixed'], true))
                                    <label class="mt-6 block">
                                        <span class="text-sm font-bold text-mk-navy">Text answer</span>
                                        <textarea name="text_answer" rows="8" class="mt-2 w-full rounded-md border border-slate-200 px-4 py-3 text-sm focus:border-mk-gold focus:ring-mk-gold">{{ old('text_answer', $submission?->text_answer) }}</textarea>
                                    </label>
                                @endif

                                @if (in_array($assignment->submission_type, ['file', 'mixed'], true))
                                    <label class="mt-6 block">
                                        <span class="text-sm font-bold text-mk-navy">Upload file</span>
                                        <input name="submission_file" type="file" class="mt-2 w-full rounded-md border border-slate-200 bg-white px-4 py-3 text-sm focus:border-mk-gold focus:ring-mk-gold">
                                        <span class="mt-2 block text-xs font-semibold text-slate-500">Allowed: pdf, doc, docx, txt, zip, png, jpg, jpeg. Max 10MB.</span>
                                    </label>
                                    @if ($submissionFileUrl)
                                        <p class="mt-2 text-sm text-slate-600">Current file: <a href="{{ $submissionFileUrl }}" target="_blank" rel="noopener noreferrer" class="font-bold text-mk-navy underline decoration-mk-gold underline-offset-4">{{ basename($submission->file_path) }}</a></p>
                                    @endif
                                @endif

                                @if (in_array($assignment->submission_type, ['link', 'mixed'], true))
                                    <label class="mt-6 block">
                                        <span class="text-sm font-bold text-mk-navy">External link</span>
                                        <input name="external_link" type="url" value="{{ old('external_link', $submission?->external_link) }}" class="mt-2 w-full rounded-md border border-slate-200 px-4 py-3 text-sm focus:border-mk-gold focus:ring-mk-gold">
                                    </label>
                                @endif

                                <x-button type="submit" class="mt-6 w-full sm:w-auto">
                                    {{ $submission ? 'Resubmit Assignment' : 'Submit Assignment' }}
                                </x-button>
                            </x-card>
                        </form>
                    @else
                        <x-card>
                            <h2 class="text-xl font-extrabold text-mk-navy">Submission locked</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">You already have an active submission. You can submit again only if your instructor requests a resubmission.</p>
                        </x-card>
                    @endif
                </main>

                <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
                    <x-card>
                        <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Assignment details</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-slate-500">Type</dt>
                                <dd class="font-bold capitalize text-mk-navy">{{ $assignment->submission_type }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-slate-500">Questions</dt>
                                <dd class="font-bold text-mk-navy">{{ $assignment->questions->count() }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-slate-500">Max score</dt>
                                <dd class="font-bold text-mk-navy">{{ $assignment->max_score }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="font-semibold text-slate-500">Late work</dt>
                                <dd class="font-bold text-mk-navy">{{ $assignment->allow_late_submission ? 'Allowed' : 'Closed' }}</dd>
                            </div>
                        </dl>
                    </x-card>

                    <x-card highlighted>
                        <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Course</p>
                        <h3 class="mt-3 text-xl font-extrabold text-mk-navy">{{ $course->title }}</h3>
                        <x-button :href="route('student.assignments')" variant="secondary" class="mt-5 w-full">All Assignments</x-button>
                    </x-card>
                </aside>
            </div>
        </div>
    </section>
</x-dashboard-layout>


