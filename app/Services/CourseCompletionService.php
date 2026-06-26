<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\CourseCompletionRule;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LiveClass;
use App\Models\LiveClassAttendance;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

class CourseCompletionService
{
    public function calculate(User $user, Course $course): CourseCompletion
    {
        $rule = $course->completionRule()
            ->where('status', CourseCompletionRule::STATUS_ACTIVE)
            ->first();

        $lessonIds = $this->publishedLessonIds($course);
        $quizIds = $this->publishedQuizIds($course);
        $assignmentIds = $this->publishedAssignmentIds($course);
        $liveClassIds = $this->completedLiveClassIds($course);

        $lessonPercentage = $this->lessonPercentage($user, $course, $lessonIds);
        $quizPercentage = $this->quizPercentage($user, $quizIds);
        $assignmentPercentage = $this->assignmentPercentage($user, $assignmentIds);
        $liveAttendancePercentage = $rule?->required_live_class_attendance_percentage !== null
            ? $this->liveAttendancePercentage($user, $liveClassIds)
            : null;

        $eligible = $this->isEligible(
            user: $user,
            rule: $rule,
            lessonPercentage: $lessonPercentage,
            quizPercentage: $quizPercentage,
            assignmentPercentage: $assignmentPercentage,
            liveAttendancePercentage: $liveAttendancePercentage,
            lessonCount: $lessonIds->count(),
            quizCount: $quizIds->count(),
            assignmentCount: $assignmentIds->count(),
            liveClassCount: $liveClassIds->count(),
        );

        $completion = CourseCompletion::query()->firstOrNew([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $completion->fill([
            'lesson_percentage' => $lessonPercentage,
            'quiz_percentage' => $quizPercentage,
            'assignment_percentage' => $assignmentPercentage,
            'live_attendance_percentage' => $liveAttendancePercentage,
            'is_eligible_for_certificate' => $eligible,
            'completed_at' => $eligible ? ($completion->completed_at ?? now()) : null,
            'last_checked_at' => now(),
        ])->save();

        return $completion;
    }

    public function checklist(User $user, Course $course, ?CourseCompletion $completion = null): array
    {
        $rule = $course->completionRule()
            ->where('status', CourseCompletionRule::STATUS_ACTIVE)
            ->first();
        $completion ??= $this->calculate($user, $course);

        $lessonIds = $this->publishedLessonIds($course);
        $quizIds = $this->publishedQuizIds($course);
        $assignmentIds = $this->publishedAssignmentIds($course);
        $liveClassIds = $this->completedLiveClassIds($course);
        $passedQuizCount = $this->passedQuizCount($user, $quizIds);
        $submittedAssignmentCount = $this->submittedAssignmentCount($user, $assignmentIds);
        $attendedLiveClassCount = $this->attendedLiveClassCount($user, $liveClassIds);
        $finalQuizPassed = ! $rule?->require_final_quiz_passed || $this->finalQuizPassed($user, $rule);

        return [
            'rule' => $rule,
            'completion' => $completion,
            'lessons' => [
                'completed' => LessonProgress::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->whereIn('lesson_id', $lessonIds)
                    ->where('status', LessonProgress::STATUS_COMPLETED)
                    ->count(),
                'total' => $lessonIds->count(),
                'percentage' => $completion->lesson_percentage,
                'required' => $rule?->require_all_lessons ? 100 : ($rule?->required_lesson_percentage ?? 80),
                'passed' => $rule?->require_all_lessons
                    ? $completion->lesson_percentage === 100 && $lessonIds->isNotEmpty()
                    : $completion->lesson_percentage >= ($rule?->required_lesson_percentage ?? 80),
            ],
            'quizzes' => [
                'passed_count' => $passedQuizCount,
                'total' => $quizIds->count(),
                'percentage' => $completion->quiz_percentage,
                'required' => $rule?->require_all_published_quizzes_passed ? 100 : ($rule?->required_quiz_percentage ?? 50),
                'passed' => $quizIds->isEmpty() || ($rule?->require_all_published_quizzes_passed
                    ? $passedQuizCount >= $quizIds->count()
                    : $completion->quiz_percentage >= ($rule?->required_quiz_percentage ?? 50)),
            ],
            'assignments' => [
                'submitted_count' => $submittedAssignmentCount,
                'total' => $assignmentIds->count(),
                'percentage' => $completion->assignment_percentage,
                'required' => $rule?->require_all_published_assignments_submitted ? 100 : null,
                'passed' => $assignmentIds->isEmpty() || ! $rule?->require_all_published_assignments_submitted || $submittedAssignmentCount >= $assignmentIds->count(),
            ],
            'final_quiz' => [
                'required' => (bool) $rule?->require_final_quiz_passed,
                'title' => $rule?->finalQuiz?->title,
                'passed' => $finalQuizPassed,
            ],
            'live_classes' => [
                'attended_count' => $attendedLiveClassCount,
                'total' => $liveClassIds->count(),
                'percentage' => $completion->live_attendance_percentage,
                'required' => $rule?->required_live_class_attendance_percentage,
                'passed' => $rule?->required_live_class_attendance_percentage === null
                    || $completion->live_attendance_percentage >= $rule->required_live_class_attendance_percentage,
            ],
        ];
    }

    private function isEligible(
        User $user,
        ?CourseCompletionRule $rule,
        int $lessonPercentage,
        int $quizPercentage,
        int $assignmentPercentage,
        ?int $liveAttendancePercentage,
        int $lessonCount,
        int $quizCount,
        int $assignmentCount,
        int $liveClassCount,
    ): bool {
        $requiredLessonPercentage = $rule?->require_all_lessons ? 100 : ($rule?->required_lesson_percentage ?? 80);
        $lessonsOk = $lessonCount > 0 && $lessonPercentage >= $requiredLessonPercentage;
        $quizzesOk = $quizCount === 0 || ($rule?->require_all_published_quizzes_passed
            ? $quizPercentage === 100
            : $quizPercentage >= ($rule?->required_quiz_percentage ?? 50));
        $assignmentsOk = $assignmentCount === 0
            || ! $rule?->require_all_published_assignments_submitted
            || $assignmentPercentage === 100;
        $finalQuizOk = ! $rule?->require_final_quiz_passed || $this->finalQuizPassed($user, $rule);
        $liveOk = $rule?->required_live_class_attendance_percentage === null
            || $liveClassCount === 0
            || $liveAttendancePercentage >= $rule->required_live_class_attendance_percentage;

        return $lessonsOk && $quizzesOk && $assignmentsOk && $finalQuizOk && $liveOk;
    }

    private function lessonPercentage(User $user, Course $course, $lessonIds): int
    {
        if ($lessonIds->isEmpty()) {
            return 0;
        }

        $completed = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('status', LessonProgress::STATUS_COMPLETED)
            ->count();

        return $this->percentage($completed, $lessonIds->count());
    }

    private function quizPercentage(User $user, $quizIds): int
    {
        if ($quizIds->isEmpty()) {
            return 100;
        }

        return $this->percentage($this->passedQuizCount($user, $quizIds), $quizIds->count());
    }

    private function assignmentPercentage(User $user, $assignmentIds): int
    {
        if ($assignmentIds->isEmpty()) {
            return 100;
        }

        return $this->percentage($this->submittedAssignmentCount($user, $assignmentIds), $assignmentIds->count());
    }

    private function liveAttendancePercentage(User $user, $liveClassIds): int
    {
        if ($liveClassIds->isEmpty()) {
            return 100;
        }

        return $this->percentage($this->attendedLiveClassCount($user, $liveClassIds), $liveClassIds->count());
    }

    private function publishedLessonIds(Course $course)
    {
        return Lesson::query()
            ->whereHas('module', fn ($query) => $query
                ->where('course_id', $course->id)
                ->where('status', Course::STATUS_PUBLISHED))
            ->where('status', Course::STATUS_PUBLISHED)
            ->pluck('id');
    }

    private function publishedQuizIds(Course $course)
    {
        return Quiz::query()
            ->where('status', Quiz::STATUS_PUBLISHED)
            ->whereHas('lesson.module', fn ($query) => $query
                ->where('course_id', $course->id)
                ->where('status', Course::STATUS_PUBLISHED))
            ->pluck('id');
    }

    private function publishedAssignmentIds(Course $course)
    {
        return Assignment::query()
            ->where('status', Assignment::STATUS_PUBLISHED)
            ->whereHas('lesson.module', fn ($query) => $query
                ->where('course_id', $course->id)
                ->where('status', Course::STATUS_PUBLISHED))
            ->pluck('id');
    }

    private function completedLiveClassIds(Course $course)
    {
        return LiveClass::query()
            ->where('status', LiveClass::STATUS_COMPLETED)
            ->where(function ($query) use ($course): void {
                $query->where('course_id', $course->id)
                    ->orWhereHas('module', fn ($moduleQuery) => $moduleQuery->where('course_id', $course->id))
                    ->orWhereHas('lesson.module', fn ($moduleQuery) => $moduleQuery->where('course_id', $course->id));
            })
            ->pluck('id');
    }

    private function passedQuizCount(User $user, $quizIds): int
    {
        if ($quizIds->isEmpty()) {
            return 0;
        }

        return QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->where('status', QuizAttempt::STATUS_PASSED)
            ->distinct('quiz_id')
            ->count('quiz_id');
    }

    private function submittedAssignmentCount(User $user, $assignmentIds): int
    {
        if ($assignmentIds->isEmpty()) {
            return 0;
        }

        return AssignmentSubmission::query()
            ->where('user_id', $user->id)
            ->whereIn('assignment_id', $assignmentIds)
            ->whereIn('status', [AssignmentSubmission::STATUS_SUBMITTED, AssignmentSubmission::STATUS_GRADED])
            ->distinct('assignment_id')
            ->count('assignment_id');
    }

    private function attendedLiveClassCount(User $user, $liveClassIds): int
    {
        if ($liveClassIds->isEmpty()) {
            return 0;
        }

        return LiveClassAttendance::query()
            ->where('user_id', $user->id)
            ->whereIn('live_class_id', $liveClassIds)
            ->where('status', LiveClassAttendance::STATUS_ATTENDED)
            ->distinct('live_class_id')
            ->count('live_class_id');
    }

    private function finalQuizPassed(User $user, CourseCompletionRule $rule): bool
    {
        if (! $rule->final_quiz_id) {
            return false;
        }

        return QuizAttempt::query()
            ->where('user_id', $user->id)
            ->where('quiz_id', $rule->final_quiz_id)
            ->where('status', QuizAttempt::STATUS_PASSED)
            ->exists();
    }

    private function percentage(int $part, int $total): int
    {
        return $total > 0 ? (int) round(($part / $total) * 100) : 0;
    }
}
