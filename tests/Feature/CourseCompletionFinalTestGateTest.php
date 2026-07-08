<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\CourseCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCompletionFinalTestGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_with_final_test_is_not_complete_until_final_test_is_passed(): void
    {
        [$student, $course] = $this->courseWithCompletedVideoAndReadingLessons();
        $finalTest = Quiz::create([
            'course_id' => $course->id,
            'lesson_id' => null,
            'quiz_type' => Quiz::TYPE_FINAL_TEST,
            'title' => 'Final Test',
            'passing_score' => 60,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        $service = app(CourseCompletionService::class);
        $completion = $service->calculate($student, $course);

        $this->assertFalse($completion->is_eligible_for_certificate);
        $this->assertNull($completion->completed_at);

        QuizAttempt::create([
            'quiz_id' => $finalTest->id,
            'user_id' => $student->id,
            'score' => 6,
            'total_points' => 10,
            'percentage' => 60,
            'status' => QuizAttempt::STATUS_PASSED,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $completion = $service->calculate($student, $course)->refresh();

        $this->assertTrue($completion->is_eligible_for_certificate);
        $this->assertNotNull($completion->completed_at);
    }

    public function test_course_without_final_test_still_uses_existing_completion_rules_and_does_not_duplicate_record(): void
    {
        [$student, $course] = $this->courseWithCompletedVideoAndReadingLessons();
        $service = app(CourseCompletionService::class);

        $firstCompletion = $service->calculate($student, $course);
        $secondCompletion = $service->calculate($student, $course);

        $this->assertTrue($firstCompletion->is_eligible_for_certificate);
        $this->assertTrue($secondCompletion->is_eligible_for_certificate);
        $this->assertSame($firstCompletion->id, $secondCompletion->id);
        $this->assertSame(1, CourseCompletion::query()->where('user_id', $student->id)->where('course_id', $course->id)->count());
    }

    private function courseWithCompletedVideoAndReadingLessons(): array
    {
        $student = User::create([
            'name' => 'Completion Student',
            'email' => 'completion-student-'.str()->random(8).'@example.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
        $academy = Academy::factory()->create(['status' => Academy::STATUS_PUBLISHED]);
        $course = Course::factory()->create([
            'academy_id' => $academy->id,
            'status' => Course::STATUS_PUBLISHED,
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
        ]);
        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Completion Module',
            'slug' => 'completion-module-'.str()->random(8),
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $videoLesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Video Lesson',
            'slug' => 'video-lesson-'.str()->random(8),
            'lesson_type' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $readingLesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Reading Lesson',
            'slug' => 'reading-lesson-'.str()->random(8),
            'lesson_type' => 'text',
            'content' => 'Read this lesson.',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        foreach ([$videoLesson, $readingLesson] as $lesson) {
            LessonProgress::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'lesson_id' => $lesson->id,
                'status' => LessonProgress::STATUS_COMPLETED,
                'progress_percent' => 100,
                'started_at' => now(),
                'completed_at' => now(),
            ]);
        }

        return [$student, $course];
    }
}
