<x-dashboard-layout role="instructor" :title="$course->title.' Quiz Attempts'" description="MK Scholars instructor quiz attempts preview.">
    <section class="bg-white py-16">
        <div class="mk-container">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-section-header eyebrow="Quiz attempts" :title="$course->title" description="Review attempts and award marks for short and long answers. Totals update automatically." />
                <x-button :href="route('instructor.courses.show', $course)" variant="secondary">Back to Course</x-button>
            </div>

            @include('instructor.partials.nav')
            @if (session('status'))
                <p class="mt-4 text-sm font-bold text-emerald-700">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <p class="mt-4 text-sm font-bold text-red-700">{{ $errors->first() }}</p>
            @endif

            <x-card class="mt-10 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Quiz</th>
                                <th class="px-4 py-3">Score</th>
                                <th class="px-4 py-3">Percentage</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Attempt Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($attempts as $attempt)
                                <tr>
                                    <td class="px-4 py-3 font-bold text-mk-navy">{{ $attempt->user?->name ?? 'Student' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $attempt->quiz?->title ?? 'Quiz' }}</td>
                                    <td class="px-4 py-3 font-bold text-mk-navy">{{ $attempt->score }}/{{ $attempt->total_points }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $attempt->percentage }}%</td>
                                    <td class="px-4 py-3"><x-badge :tone="$attempt->status === 'passed' ? 'green' : 'gray'">{{ $attempt->status === \App\Models\QuizAttempt::STATUS_SUBMITTED ? 'Waiting for grading' : str_replace('_', ' ', $attempt->status) }}</x-badge></td>
                                    <td class="px-4 py-3 text-slate-600">{{ $attempt->submitted_at?->format('M j, Y g:i A') ?? $attempt->started_at?->format('M j, Y g:i A') ?? 'N/A' }}</td>
                                </tr>
                                @if ($attempt->submitted_at)
                                    @foreach ($attempt->answers as $answer)
                                        @if ($answer->question?->acceptsTextAnswer())
                                            <tr><td colspan="6" class="px-4 py-4 bg-slate-50">
                                                <p class="font-bold text-mk-navy">{{ $answer->question->question_text }}</p>
                                                <p class="mt-2 whitespace-pre-wrap text-slate-600">{{ $answer->answer_text ?: 'No answer submitted' }}</p>
                                                <form method="POST" action="{{ route('instructor.quiz-answers.grade', ['attempt' => $attempt, 'answer' => $answer]) }}" class="mt-3 flex flex-wrap items-end gap-3">
                                                    @csrf
                                                    <label class="text-sm font-bold">Marks / {{ $answer->question->points }}
                                                        <input type="number" name="points" min="0" max="{{ $answer->question->points }}" step="1" value="{{ $answer->graded_at ? $answer->points_awarded : '' }}" required class="block w-24 rounded border border-slate-200 p-2">
                                                    </label>
                                                    <label class="text-sm font-bold">Feedback (optional)
                                                        <textarea name="feedback" maxlength="5000" class="block rounded border border-slate-200 p-2">{{ $answer->feedback }}</textarea>
                                                    </label>
                                                    <x-button type="submit" size="sm">{{ $answer->graded_at ? 'Update Marks' : 'Save Marks' }}</x-button>
                                                    <span class="text-xs text-slate-500">{{ $answer->graded_at ? 'Graded' : 'Waiting for grading' }}</span>
                                                </form>
                                            </td></tr>
                                        @endif
                                    @endforeach
                                @endif
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-sm text-slate-600">No quiz attempts found for this course.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </section>
</x-dashboard-layout>

