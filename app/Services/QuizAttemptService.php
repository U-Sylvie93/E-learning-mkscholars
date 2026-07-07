<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function publishedQuestions(Quiz $quiz): Collection
    {
        return $quiz->questions()
            ->where('status', QuizQuestion::STATUS_PUBLISHED)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with(['options' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->get();
    }

    public function completedAttemptCount(User $user, Quiz $quiz): int
    {
        return QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->whereIn('status', [
                QuizAttempt::STATUS_PASSED,
                QuizAttempt::STATUS_FAILED,
                QuizAttempt::STATUS_SUBMITTED,
            ])
            ->count();
    }

    public function activeAttempt(User $user, Quiz $quiz): ?QuizAttempt
    {
        $attempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
            ->latest('started_at')
            ->first();

        if ($attempt && $this->isExpired($attempt)) {
            $this->submit($attempt, $quiz);

            return null;
        }

        return $attempt;
    }

    public function startOrResume(User $user, Quiz $quiz): QuizAttempt
    {
        $questions = $this->publishedQuestions($quiz);

        if ($quiz->status !== Quiz::STATUS_PUBLISHED) {
            abort(404);
        }

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'quiz' => 'This quiz does not have published questions yet.',
            ]);
        }

        if ($questions->contains(fn ($question): bool => $question->options->isEmpty())) {
            throw ValidationException::withMessages([
                'quiz' => 'This quiz is missing answer options. Please contact your instructor.',
            ]);
        }

        if ($activeAttempt = $this->activeAttempt($user, $quiz)) {
            return $activeAttempt;
        }

        if ($quiz->max_attempts !== null && $this->completedAttemptCount($user, $quiz) >= $quiz->max_attempts) {
            throw ValidationException::withMessages([
                'quiz' => 'You have reached the maximum number of attempts for this quiz.',
            ]);
        }

        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => 0,
            'total_points' => (int) $questions->sum('points'),
            'percentage' => 0,
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'expires_at' => $quiz->time_limit_minutes ? now()->addMinutes($quiz->time_limit_minutes) : null,
            'current_question_index' => 0,
        ]);
    }

    public function saveAnswer(QuizAttempt $attempt, Quiz $quiz, int $questionIndex, int $optionId): QuizAnswer
    {
        if ($attempt->status !== QuizAttempt::STATUS_IN_PROGRESS || $attempt->submitted_at) {
            throw ValidationException::withMessages([
                'quiz' => 'This quiz attempt has already been submitted.',
            ]);
        }

        if ($this->isExpired($attempt)) {
            $this->submit($attempt, $quiz);

            throw ValidationException::withMessages([
                'quiz' => 'Time has expired for this attempt. Your saved answers were submitted.',
            ]);
        }

        $questions = $this->publishedQuestions($quiz)->values();
        $question = $questions->get($questionIndex);

        if (! $question) {
            throw ValidationException::withMessages([
                'quiz' => 'The requested quiz question is not available.',
            ]);
        }

        $selectedOption = $question->options->firstWhere('id', $optionId);

        if (! $selectedOption instanceof QuizOption) {
            throw ValidationException::withMessages([
                'option_id' => 'The selected answer does not belong to this question.',
            ]);
        }

        $answer = QuizAnswer::updateOrCreate(
            [
                'quiz_attempt_id' => $attempt->id,
                'quiz_question_id' => $question->id,
            ],
            [
                'quiz_option_id' => $selectedOption->id,
                'is_correct' => (bool) $selectedOption->is_correct,
                'points_awarded' => $selectedOption->is_correct ? (int) $question->points : 0,
            ],
        );

        $attempt->forceFill([
            'current_question_index' => min($questionIndex + 1, max($questions->count() - 1, 0)),
        ])->save();

        return $answer;
    }

    public function submit(QuizAttempt $attempt, Quiz $quiz): QuizAttempt
    {
        if ($attempt->submitted_at || $attempt->status !== QuizAttempt::STATUS_IN_PROGRESS) {
            return $attempt;
        }

        return DB::transaction(function () use ($attempt, $quiz): QuizAttempt {
            $questions = $this->publishedQuestions($quiz);
            $totalPoints = (int) $questions->sum('points');
            $score = (int) QuizAnswer::query()
                ->where('quiz_attempt_id', $attempt->id)
                ->whereIn('quiz_question_id', $questions->pluck('id'))
                ->sum('points_awarded');
            $percentage = $totalPoints > 0 ? (int) round(($score / $totalPoints) * 100) : 0;

            $attempt->update([
                'score' => $score,
                'total_points' => $totalPoints,
                'percentage' => $percentage,
                'status' => $percentage >= $quiz->passing_score
                    ? QuizAttempt::STATUS_PASSED
                    : QuizAttempt::STATUS_FAILED,
                'submitted_at' => now(),
            ]);

            return $attempt->refresh();
        });
    }

    public function isExpired(QuizAttempt $attempt): bool
    {
        return $attempt->expires_at !== null && now()->greaterThanOrEqualTo($attempt->expires_at);
    }

    public function secondsRemaining(QuizAttempt $attempt): ?int
    {
        if (! $attempt->expires_at) {
            return null;
        }

        return max(0, now()->diffInSeconds($attempt->expires_at, false));
    }
}
