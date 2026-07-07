<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonActivity;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Rules\YouTubeUrl;
use App\Support\YouTubeEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LessonYouTubeVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_learning_page_renders_supported_youtube_urls_as_safe_embed(): void
    {
        $cases = [
            'watch-url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'watch-url-query' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PL123&t=42s',
            'short-url' => 'https://youtu.be/dQw4w9WgXcQ',
            'short-url-query' => 'https://youtu.be/dQw4w9WgXcQ?t=42',
            'embed-url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'embed-url-query' => 'https://www.youtube.com/embed/dQw4w9WgXcQ?start=42',
            'shorts-url' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        ];

        foreach ($cases as $slugSuffix => $videoUrl) {
            [$student, $course, $lesson] = $this->createLearningScenario($videoUrl, $slugSuffix);

            $this->actingAs($student)
                ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
                ->assertOk()
                ->assertSee('<iframe', false)
                ->assertSee('src="https://www.youtube.com/embed/dQw4w9WgXcQ"', false)
                ->assertSee('allowfullscreen', false)
                ->assertSee('Written lesson notes display safely.');
        }
    }

    public function test_youtube_helper_rejects_invalid_or_unsafe_video_inputs(): void
    {
        $invalidUrls = [
            null,
            '',
            'not-a-url',
            '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>',
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ',
            'https://notyoutube.com/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=too-short',
            'https://youtu.be/not-valid-id!',
            'ftp://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ];

        foreach ($invalidUrls as $url) {
            $this->assertNull(YouTubeEmbed::embedUrl($url), 'Expected invalid YouTube URL to be rejected: '.(string) $url);
        }
    }

    public function test_youtube_url_validation_rule_accepts_empty_and_supported_urls(): void
    {
        $validUrls = [
            null,
            '',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PL123&t=42s',
            'https://youtu.be/dQw4w9WgXcQ?t=42',
            'https://www.youtube.com/embed/dQw4w9WgXcQ?start=42',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        ];

        foreach ($validUrls as $url) {
            $validator = Validator::make(
                ['video_url' => $url],
                ['video_url' => ['nullable', 'url', new YouTubeUrl()]]
            );

            $this->assertTrue($validator->passes(), 'Expected valid YouTube URL to pass: '.(string) $url);
        }
    }

    public function test_youtube_url_validation_rule_rejects_unsafe_or_non_youtube_urls(): void
    {
        $invalidUrls = [
            'https://vimeo.com/123456789',
            'https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=too-short',
            '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>',
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
        ];

        foreach ($invalidUrls as $url) {
            $validator = Validator::make(
                ['video_url' => $url],
                ['video_url' => ['nullable', 'url', new YouTubeUrl()]]
            );

            $this->assertFalse($validator->passes(), 'Expected unsafe YouTube URL to fail: '.$url);
            $this->assertTrue($validator->errors()->has('video_url'));
        }
    }

    public function test_admin_lesson_create_page_loads_without_video_url_validation_crash(): void
    {
        $admin = User::create([
            'name' => 'Lesson Admin',
            'email' => 'lesson-admin@example.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/lessons/create')
            ->assertOk()
            ->assertSee('YouTube video URL')
            ->assertSee('Do not paste iframe HTML');
    }
    public function test_invalid_non_youtube_video_url_does_not_render_iframe(): void
    {
        [$student, $course, $lesson] = $this->createLearningScenario('https://vimeo.com/123456789', 'invalid-non-youtube');

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertDontSee('<iframe', false)
            ->assertDontSee('https://vimeo.com/123456789', false)
            ->assertSee('The video link for this lesson is not available yet.');
    }

    public function test_fake_youtube_domain_does_not_render_iframe(): void
    {
        [$student, $course, $lesson] = $this->createLearningScenario('https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ', 'fake-youtube-domain');

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertDontSee('<iframe', false)
            ->assertDontSee('youtube.com.evil.com', false)
            ->assertSee('The video link for this lesson is not available yet.');
    }

    public function test_unsafe_video_url_does_not_render_iframe(): void
    {
        foreach (['javascript:alert(1)' => 'unsafe-javascript', 'data:text/html,<script>alert(1)</script>' => 'unsafe-data-url'] as $url => $slugSuffix) {
            [$student, $course, $lesson] = $this->createLearningScenario($url, $slugSuffix);

            $this->actingAs($student)
                ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
                ->assertOk()
                ->assertDontSee('<iframe', false)
                ->assertDontSee($url, false)
                ->assertSee('The video link for this lesson is not available yet.');
        }
    }

    public function test_raw_iframe_video_input_does_not_render_iframe(): void
    {
        [$student, $course, $lesson] = $this->createLearningScenario('<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>', 'raw-iframe-input');

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertDontSee('<iframe', false)
            ->assertDontSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('The video link for this lesson is not available yet.');
    }

    public function test_empty_video_url_does_not_crash_and_written_content_still_displays_safely(): void
    {
        [$student, $course, $lesson] = $this->createLearningScenario(null, 'empty-video', 'Written lesson notes display safely. <iframe src="https://evil.example/embed"></iframe>');

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertDontSee('<iframe src="https://evil.example/embed"', false)
            ->assertSee('Written lesson notes display safely.')
            ->assertDontSee('&lt;iframe src=&quot;https://evil.example/embed&quot;&gt;&lt;/iframe&gt;', false);
    }

    public function test_student_must_have_course_access_to_view_video_lesson(): void
    {
        [$student, $course, $lesson] = $this->createLearningScenario('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'access-required', enroll: false);

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertForbidden();
    }

    public function test_learning_page_uses_student_workspace_with_sidebar_and_tools_panel(): void
    {
        [$student, $course, $lesson] = $this->createLearningScenario('https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'workspace-layout');

        Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Workspace readiness quiz',
            'description' => 'Check your understanding.',
            'passing_score' => 60,
            'status' => Quiz::STATUS_PUBLISHED,
        ]);

        Assignment::create([
            'lesson_id' => $lesson->id,
            'title' => 'Workspace reflection assignment',
            'instructions' => 'Submit your notes.',
            'submission_type' => Assignment::TYPE_TEXT,
            'max_score' => 100,
            'allow_late_submission' => true,
            'status' => Assignment::STATUS_PUBLISHED,
        ]);

        LessonActivity::create([
            'lesson_id' => $lesson->id,
            'activity_type' => 'download',
            'title' => 'Lesson worksheet',
            'instructions' => 'Use this worksheet while studying.',
            'resource_url' => 'https://example.test/worksheet.pdf',
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $lesson->id]))
            ->assertOk()
            ->assertSee('Student dashboard')
            ->assertSee('data-testid="learning-workspace"', false)
            ->assertSee('data-testid="learning-sidebar"', false)
            ->assertSee('data-testid="learning-sidebar-toggle"', false)
            ->assertSee('data-testid="learning-main-content"', false)
            ->assertSee('data-testid="learning-video-card"', false)
            ->assertSee('data-testid="learning-video-fullscreen"', false)
            ->assertSee('data-testid="learning-tools-panel"', false)
            ->assertSee('Learning path')
            ->assertSee('Start Quiz')
            ->assertSee('Open Assignment')
            ->assertSee('Materials')
            ->assertSee('Resources')
            ->assertSee('Open resource')
            ->assertSee('Completion')
            ->assertSee('<iframe', false)
            ->assertSee('src="https://www.youtube.com/embed/dQw4w9WgXcQ"', false)
            ->assertSee('Mark Lesson Complete')
            ->assertDontSee('Quick links');
    }

    public function test_learning_page_handles_empty_tools_long_content_navigation_and_completed_state(): void
    {
        $longTitle = 'A very detailed lesson title about scholarship essays, technical interviews, career planning, and portfolio storytelling that should wrap cleanly on every screen size';
        $longContent = str_repeat('Long written lesson content stays readable and wraps safely without creating horizontal scrolling. ', 12);
        [$student, $course, $firstLesson] = $this->createLearningScenario(null, 'polish-layout', $longContent);
        $firstLesson->update(['title' => $longTitle]);

        $nextLesson = Lesson::create([
            'module_id' => $firstLesson->module_id,
            'title' => 'Next navigation lesson',
            'slug' => 'next-navigation-lesson',
            'summary' => 'The second published lesson.',
            'lesson_type' => 'text',
            'content' => 'Second lesson content.',
            'duration_minutes' => 8,
            'sort_order' => 2,
            'is_free_preview' => false,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        LessonProgress::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'lesson_id' => $firstLesson->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $firstLesson->id]))
            ->assertOk()
            ->assertSee('Lesson Tools')
            ->assertSee('data-testid="learning-workspace"', false)
            ->assertSee('data-testid="learning-sidebar"', false)
            ->assertSee('data-testid="learning-main-content"', false)
            ->assertSee('data-testid="learning-tools-panel"', false)
            ->assertSee('Learning path')
            ->assertSee($longTitle)
            ->assertSee('Long written lesson content stays readable')
            ->assertSee('Next:')
            ->assertSee('Lesson Completed')
            ->assertDontSee('Mark Complete Again')
            ->assertSee('No quiz is attached to this lesson.')
            ->assertSee('No assignments are attached to this lesson yet.')
            ->assertSee('No materials attached.')
            ->assertDontSee('<iframe', false)
            ->assertDontSee('Quick links');

        $this->actingAs($student)
            ->get(route('student.courses.learn', ['course' => $course, 'lesson' => $nextLesson->id]))
            ->assertOk()
            ->assertSee('Previous:')
            ->assertSee('Mark Lesson Complete')
            ->assertDontSee('Lesson Completed');
    }
    private function createLearningScenario(?string $videoUrl, string $slugSuffix, ?string $content = null, bool $enroll = true): array
    {
        $student = User::create([
            'name' => 'Video Student '.str($slugSuffix)->headline(),
            'email' => 'video-student-'.str($slugSuffix)->slug().'@example.test',
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        $academy = Academy::factory()->create([
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $course = Course::factory()->create([
            'academy_id' => $academy->id,
            'title' => 'Video Course '.$slugSuffix,
            'slug' => 'video-course-'.$slugSuffix,
            'status' => Course::STATUS_PUBLISHED,
            'is_free' => true,
            'access_type' => Course::ACCESS_FREE,
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Video Module '.$slugSuffix,
            'slug' => 'video-module-'.$slugSuffix,
            'summary' => 'Video module summary.',
            'sort_order' => 1,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Video Lesson '.$slugSuffix,
            'slug' => 'video-lesson-'.$slugSuffix,
            'summary' => 'Video lesson summary.',
            'lesson_type' => 'video',
            'video_url' => $videoUrl,
            'content' => $content ?? 'Written lesson notes display safely.',
            'duration_minutes' => 12,
            'sort_order' => 1,
            'is_free_preview' => false,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        if ($enroll) {
            Enrollment::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now(),
            ]);
        }

        return [$student, $course, $lesson];
    }
}




