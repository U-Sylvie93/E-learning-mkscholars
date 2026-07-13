<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseCompletionUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_and_completed_course_cards_show_correct_actions_and_certificate_status(): void
    {
        [$student, $incompleteCourse] = $this->enrolledCourse('incomplete-card');
        [, $completedCourse] = $this->enrolledCourse('completed-card', $student);
        $completedLesson = $completedCourse->modules()->first()->lessons()->first();

        LessonProgress::create([
            'user_id' => $student->id,
            'course_id' => $completedCourse->id,
            'lesson_id' => $completedLesson->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $certificate = Certificate::create([
            'user_id' => $student->id,
            'course_id' => $completedCourse->id,
            'student_name' => $student->name,
            'course_title' => $completedCourse->title,
            'status' => Certificate::STATUS_ISSUED,
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertSee($incompleteCourse->title)
            ->assertSee('Continue Learning')
            ->assertSee($completedCourse->title)
            ->assertSee('Completed')
            ->assertSee('Certificate Issued')
            ->assertSee('View Certificate')
            ->assertSee(route('student.certificates.show', $certificate), false);
    }

    public function test_pending_certificate_status_appears_on_completed_course_card(): void
    {
        [$student, $course] = $this->enrolledCourse('pending-certificate-card');
        $lesson = $course->modules()->first()->lessons()->first();

        LessonProgress::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'student_name' => $student->name,
            'course_title' => $course->title,
            'status' => Certificate::STATUS_PENDING,
        ]);

        $this->actingAs($student)
            ->get(route('student.my-courses'))
            ->assertOk()
            ->assertSee('Certificate Pending Approval');
    }

    public function test_video_and_reading_completion_labels_change_after_completion(): void
    {
        [$student, $course] = $this->enrolledCourse('lesson-labels', null, [
            ['title' => 'Video Lesson', 'slug' => 'video-lesson', 'lesson_type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['title' => 'Reading Lesson', 'slug' => 'reading-lesson', 'lesson_type' => 'text', 'content' => 'Read me.'],
        ]);
        $videoLesson = $course->modules()->first()->lessons()->where('lesson_type', 'video')->first();
        $readingLesson = $course->modules()->first()->lessons()->where('lesson_type', 'text')->first();

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $videoLesson->id]))
            ->assertOk()
            ->assertSee('Mark Video as Completed')
            ->assertDontSee('Video Completed');

        $this->actingAs($student)
            ->post(route('student.lessons.complete', ['course' => $course, 'lesson' => $videoLesson]))
            ->assertRedirect();

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $videoLesson->id]))
            ->assertOk()
            ->assertSee('Video Completed')
            ->assertDontSee('Mark Video as Completed');

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $readingLesson->id]))
            ->assertOk()
            ->assertSee('Mark Reading as Completed');

        $this->actingAs($student)
            ->post(route('student.lessons.complete', ['course' => $course, 'lesson' => $readingLesson]))
            ->assertRedirect();

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $readingLesson->id]))
            ->assertOk()
            ->assertSee('Reading Completed')
            ->assertDontSee('Mark Reading as Completed');
    }

    public function test_marking_lesson_complete_twice_does_not_duplicate_progress(): void
    {
        [$student, $course] = $this->enrolledCourse('duplicate-completion');
        $lesson = $course->modules()->first()->lessons()->first();

        $this->actingAs($student)->post(route('student.lessons.complete', ['course' => $course, 'lesson' => $lesson]));
        $this->actingAs($student)->post(route('student.lessons.complete', ['course' => $course, 'lesson' => $lesson]));

        $this->assertSame(1, LessonProgress::query()
            ->where('user_id', $student->id)
            ->where('lesson_id', $lesson->id)
            ->where('status', LessonProgress::STATUS_COMPLETED)
            ->count());
    }

    public function test_guest_and_student_without_access_cannot_mark_lesson_complete(): void
    {
        [$student, $course] = $this->enrolledCourse('access-completion');
        $lesson = $course->modules()->first()->lessons()->first();
        $otherStudent = $this->student('other-access@mkscholars.test');

        $this->post(route('student.lessons.complete', ['course' => $course, 'lesson' => $lesson]))
            ->assertRedirect(route('login'));

        $this->actingAs($otherStudent)
            ->post(route('student.lessons.complete', ['course' => $course, 'lesson' => $lesson]))
            ->assertForbidden();

        $this->assertSame(0, LessonProgress::query()
            ->where('user_id', $otherStudent->id)
            ->where('lesson_id', $lesson->id)
            ->count());
    }

    public function test_quiz_final_test_assignment_and_summary_statuses_are_clear(): void
    {
        [$student, $course] = $this->enrolledCourse('assessment-statuses', null, [
            ['title' => 'Video Lesson', 'slug' => 'assessment-video', 'lesson_type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['title' => 'Reading Lesson', 'slug' => 'assessment-reading', 'lesson_type' => 'text', 'content' => 'Read me.'],
        ]);
        $videoLesson = $course->modules()->first()->lessons()->where('lesson_type', 'video')->first();
        $readingLesson = $course->modules()->first()->lessons()->where('lesson_type', 'text')->first();
        $quiz = Quiz::create([
            'lesson_id' => $videoLesson->id,
            'course_id' => $course->id,
            'quiz_type' => Quiz::TYPE_LESSON_QUIZ,
            'title' => 'Knowledge Check',
            'passing_score' => 60,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
        $finalTest = Quiz::create([
            'course_id' => $course->id,
            'quiz_type' => Quiz::TYPE_FINAL_TEST,
            'title' => 'Final Exam',
            'passing_score' => 60,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);
        $assignment = Assignment::create([
            'lesson_id' => $videoLesson->id,
            'title' => 'Portfolio Task',
            'instructions' => 'Submit your work.',
            'submission_type' => Assignment::TYPE_TEXT,
            'max_score' => 100,
            'status' => Assignment::STATUS_PUBLISHED,
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $videoLesson->id]))
            ->assertOk()
            ->assertSee('Start Quiz')
            ->assertSee('Start Test')
            ->assertSee('Submit Assignment')
            ->assertSee('Videos completed')
            ->assertSee('Reading lessons completed')
            ->assertSee('Quizzes completed')
            ->assertSee('Assignments submitted/completed')
            ->assertSee('Final Test Required');

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 6,
            'total_points' => 10,
            'percentage' => 60,
            'status' => QuizAttempt::STATUS_PASSED,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);
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
        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'text_answer' => 'Submitted answer',
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
        LessonProgress::updateOrCreate(
            ['user_id' => $student->id, 'lesson_id' => $videoLesson->id],
            [
                'course_id' => $course->id,
                'status' => LessonProgress::STATUS_COMPLETED,
                'progress_percent' => 100,
                'started_at' => now(),
                'completed_at' => now(),
            ],
        );
        LessonProgress::updateOrCreate(
            ['user_id' => $student->id, 'lesson_id' => $readingLesson->id],
            [
                'course_id' => $course->id,
                'status' => LessonProgress::STATUS_COMPLETED,
                'progress_percent' => 100,
                'started_at' => now(),
                'completed_at' => now(),
            ],
        );

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $videoLesson->id]))
            ->assertOk()
            ->assertSee('Quiz Completed')
            ->assertSee('Final Test Completed')
            ->assertSee('Assignment Submitted')
            ->assertSee('Course status')
            ->assertSee('Completed');
    }

    private function enrolledCourse(string $slug, ?User $student = null, ?array $lessons = null): array
    {
        $student ??= $this->student($slug.'@mkscholars.test');
        $course = $this->course($slug, $lessons);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        return [$student, $course];
    }

    private function student(string $email): User
    {
        return User::create([
            'name' => 'Student',
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
    }

    private function course(string $slug, ?array $lessons = null): Course
    {
        $academy = Academy::create([
            'name' => 'Academy '.$slug,
            'slug' => 'academy-'.$slug,
            'summary' => 'Demo academy',
            'description' => 'Demo academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);
        $course = Course::create([
            'academy_id' => $academy->id,
            'title' => 'Demo Course '.$slug,
            'slug' => $slug,
            'short_description' => 'Demo course',
            'full_description' => 'Demo course',
            'level' => 'Beginner',
            'duration' => '1 week',
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Module '.$slug,
            'slug' => 'module-'.$slug,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        foreach ($lessons ?? [['title' => 'Lesson '.$slug, 'slug' => 'lesson-'.$slug, 'lesson_type' => 'text', 'content' => 'Lesson content']] as $index => $lesson) {
            Lesson::create([
                'module_id' => $module->id,
                'title' => $lesson['title'],
                'slug' => $lesson['slug'],
                'lesson_type' => $lesson['lesson_type'],
                'video_url' => $lesson['video_url'] ?? null,
                'content' => $lesson['content'] ?? null,
                'sort_order' => $index + 1,
                'status' => Course::STATUS_PUBLISHED,
            ]);
        }

        return $course;
    }
}
