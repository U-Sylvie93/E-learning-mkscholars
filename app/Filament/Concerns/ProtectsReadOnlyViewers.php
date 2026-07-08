<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\AssignmentSubmissions\AssignmentSubmissionResource;
use App\Filament\Resources\Assignments\AssignmentResource;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Filament\Resources\CertificateSkills\CertificateSkillResource;
use App\Filament\Resources\CourseCompletionRules\CourseCompletionRuleResource;
use App\Filament\Resources\CourseCompletions\CourseCompletionResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\LessonActivities\LessonActivityResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\QuizAttempts\QuizAttemptResource;
use App\Filament\Resources\QuizOptions\QuizOptionResource;
use App\Filament\Resources\QuizQuestions\QuizQuestionResource;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Assignment;
use App\Models\Lesson;
use App\Models\LessonActivity;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait ProtectsReadOnlyViewers
{
    public static function isReadOnlyViewer(): bool
    {
        return auth()->user()?->role === User::ROLE_VIEWER;
    }

    public static function isContentEditor(): bool
    {
        return auth()->user()?->role === User::ROLE_CONTENT_EDITOR;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        if ($user->role === User::ROLE_VIEWER) {
            $permission = static::viewerPermissionForResource();

            return $permission !== null && $user->hasViewerPermission($permission);
        }

        if ($user->role === User::ROLE_CONTENT_EDITOR) {
            return static::contentPermissionForResource($user) !== null;
        }

        return false;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        if (! $user || ! static::canViewAny()) {
            return false;
        }

        if ($user->role === User::ROLE_CONTENT_EDITOR) {
            return static::recordBelongsToAssignedContentCourse($record, $user);
        }

        return true;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        if ($user->role === User::ROLE_CONTENT_EDITOR) {
            $permission = match (static::class) {
                CourseResource::class => User::CONTENT_PERMISSION_COURSES_CREATE,
                ModuleResource::class => User::CONTENT_PERMISSION_MODULES_MANAGE,
                LessonResource::class,
                LessonActivityResource::class => User::CONTENT_PERMISSION_LESSONS_MANAGE,
                QuizResource::class,
                QuizQuestionResource::class,
                QuizOptionResource::class => User::CONTENT_PERMISSION_QUIZZES_MANAGE,
                AssignmentResource::class => User::CONTENT_PERMISSION_ASSIGNMENTS_MANAGE,
                default => null,
            };

            return $permission !== null && $user->hasContentPermission($permission);
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        if ($user->role === User::ROLE_CONTENT_EDITOR) {
            return static::contentEditPermissionForResource($user) !== null
                && static::recordBelongsToAssignedContentCourse($record, $user);
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->role !== User::ROLE_CONTENT_EDITOR) {
            return $query;
        }

        $courseIds = $user->assignedContentCourseIds();

        if ($courseIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return match (static::class) {
            CourseResource::class => $query->whereIn('id', $courseIds),
            ModuleResource::class => $query->whereIn('course_id', $courseIds),
            LessonResource::class => $query->whereHas('module', fn (Builder $moduleQuery): Builder => $moduleQuery->whereIn('course_id', $courseIds)),
            LessonActivityResource::class => $query->whereHas('lesson.module', fn (Builder $moduleQuery): Builder => $moduleQuery->whereIn('course_id', $courseIds)),
            QuizResource::class => $query->where(function (Builder $quizQuery) use ($courseIds): void {
                $quizQuery->whereIn('course_id', $courseIds)
                    ->orWhereHas('lesson.module', fn (Builder $moduleQuery): Builder => $moduleQuery->whereIn('course_id', $courseIds));
            }),
            QuizAttemptResource::class => $query->whereHas('quiz', fn (Builder $quizQuery): Builder => $quizQuery
                ->whereIn('course_id', $courseIds)
                ->orWhereHas('lesson.module', fn (Builder $moduleQuery): Builder => $moduleQuery->whereIn('course_id', $courseIds))),
            QuizQuestionResource::class => $query->whereHas('quiz', fn (Builder $quizQuery): Builder => $quizQuery
                ->whereIn('course_id', $courseIds)
                ->orWhereHas('lesson.module', fn (Builder $moduleQuery): Builder => $moduleQuery->whereIn('course_id', $courseIds))),
            QuizOptionResource::class => $query->whereHas('question.quiz', fn (Builder $quizQuery): Builder => $quizQuery
                ->whereIn('course_id', $courseIds)
                ->orWhereHas('lesson.module', fn (Builder $moduleQuery): Builder => $moduleQuery->whereIn('course_id', $courseIds))),
            AssignmentResource::class => $query->whereHas('lesson.module', fn (Builder $moduleQuery): Builder => $moduleQuery->whereIn('course_id', $courseIds)),
            AssignmentSubmissionResource::class => $query->whereHas('assignment.lesson.module', fn (Builder $moduleQuery): Builder => $moduleQuery->whereIn('course_id', $courseIds)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    protected static function viewerPermissionForResource(): ?string
    {
        return match (static::class) {
            UserResource::class => User::VIEWER_PERMISSION_USERS,
            CourseResource::class,
            ModuleResource::class,
            LessonResource::class,
            LessonActivityResource::class,
            CourseCompletionRuleResource::class,
            CourseCompletionResource::class => User::VIEWER_PERMISSION_COURSES,
            PaymentResource::class => User::VIEWER_PERMISSION_PAYMENTS,
            SubscriptionResource::class => User::VIEWER_PERMISSION_SUBSCRIPTIONS,
            CertificateResource::class,
            CertificateSkillResource::class => User::VIEWER_PERMISSION_CERTIFICATES,
            QuizResource::class,
            QuizQuestionResource::class,
            QuizOptionResource::class,
            QuizAttemptResource::class => User::VIEWER_PERMISSION_QUIZZES,
            AssignmentResource::class,
            AssignmentSubmissionResource::class => User::VIEWER_PERMISSION_ASSIGNMENTS,
            default => null,
        };
    }

    protected static function contentPermissionForResource(User $user): ?string
    {
        $permissions = match (static::class) {
            CourseResource::class => [
                User::CONTENT_PERMISSION_COURSES_CREATE,
                User::CONTENT_PERMISSION_COURSES_EDIT,
            ],
            ModuleResource::class => [User::CONTENT_PERMISSION_MODULES_MANAGE],
            LessonResource::class,
            LessonActivityResource::class => [User::CONTENT_PERMISSION_LESSONS_MANAGE],
            QuizResource::class,
            QuizQuestionResource::class,
            QuizOptionResource::class => [
                User::CONTENT_PERMISSION_QUIZZES_MANAGE,
                User::CONTENT_PERMISSION_FINAL_TESTS_MANAGE,
            ],
            AssignmentResource::class => [User::CONTENT_PERMISSION_ASSIGNMENTS_MANAGE],
            default => [],
        };

        return collect($permissions)
            ->first(fn (string $permission): bool => $user->hasContentPermission($permission));
    }

    protected static function contentEditPermissionForResource(User $user): ?string
    {
        $permissions = match (static::class) {
            CourseResource::class => [User::CONTENT_PERMISSION_COURSES_EDIT],
            ModuleResource::class => [User::CONTENT_PERMISSION_MODULES_MANAGE],
            LessonResource::class,
            LessonActivityResource::class => [User::CONTENT_PERMISSION_LESSONS_MANAGE],
            QuizResource::class,
            QuizQuestionResource::class,
            QuizOptionResource::class => [
                User::CONTENT_PERMISSION_QUIZZES_MANAGE,
                User::CONTENT_PERMISSION_FINAL_TESTS_MANAGE,
            ],
            AssignmentResource::class => [User::CONTENT_PERMISSION_ASSIGNMENTS_MANAGE],
            default => [],
        };

        return collect($permissions)
            ->first(fn (string $permission): bool => $user->hasContentPermission($permission));
    }

    protected static function recordBelongsToAssignedContentCourse(Model $record, User $user): bool
    {
        return $user->canManageContentCourse(static::courseIdForRecord($record));
    }

    public static function authorizeContentEditorCreateData(array $data): void
    {
        $user = auth()->user();

        if ($user?->role !== User::ROLE_CONTENT_EDITOR || static::class === CourseResource::class) {
            return;
        }

        abort_unless($user->canManageContentCourse(static::courseIdForCreateData($data)), 403);
    }

    protected static function courseIdForRecord(Model $record): ?int
    {
        if ($record instanceof \App\Models\Course) {
            return $record->id;
        }

        if ($record instanceof Module) {
            return $record->course_id;
        }

        if ($record instanceof Lesson) {
            return $record->module?->course_id;
        }

        if ($record instanceof LessonActivity) {
            return $record->lesson?->module?->course_id;
        }

        if ($record instanceof Quiz) {
            return $record->course_id ?? $record->lesson?->module?->course_id;
        }

        if ($record instanceof QuizQuestion) {
            return $record->quiz?->course_id ?? $record->quiz?->lesson?->module?->course_id;
        }

        if ($record instanceof QuizOption) {
            $quiz = $record->question?->quiz;

            return $quiz?->course_id ?? $quiz?->lesson?->module?->course_id;
        }

        if ($record instanceof Assignment) {
            return $record->lesson?->module?->course_id;
        }

        return null;
    }

    protected static function courseIdForCreateData(array $data): ?int
    {
        return match (static::class) {
            ModuleResource::class => isset($data['course_id']) ? (int) $data['course_id'] : null,
            LessonResource::class => isset($data['module_id']) ? Module::query()->whereKey($data['module_id'])->value('course_id') : null,
            LessonActivityResource::class => isset($data['lesson_id']) ? Lesson::query()
                ->whereKey($data['lesson_id'])
                ->with('module:id,course_id')
                ->first()?->module?->course_id : null,
            QuizResource::class => isset($data['course_id']) && $data['course_id']
                ? (int) $data['course_id']
                : (isset($data['lesson_id']) ? Lesson::query()->whereKey($data['lesson_id'])->with('module:id,course_id')->first()?->module?->course_id : null),
            QuizQuestionResource::class => isset($data['quiz_id']) ? static::courseIdForRecord(Quiz::query()->whereKey($data['quiz_id'])->with('lesson.module')->first() ?? new Quiz()) : null,
            QuizOptionResource::class => isset($data['quiz_question_id']) ? static::courseIdForRecord(QuizQuestion::query()->whereKey($data['quiz_question_id'])->with('quiz.lesson.module')->first() ?? new QuizQuestion()) : null,
            AssignmentResource::class => isset($data['lesson_id']) ? Lesson::query()->whereKey($data['lesson_id'])->with('module:id,course_id')->first()?->module?->course_id : null,
            default => null,
        };
    }
}
