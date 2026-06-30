<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\AppNotification;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\MentorAssignment;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\DemoPitchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoPitchSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_pitch_seeder_creates_realistic_idempotent_demo_data(): void
    {
        $this->seed(DemoPitchSeeder::class);
        $this->seed(DemoPitchSeeder::class);

        $this->assertSame(4, User::query()->whereIn('email', [
            'admin@mkscholars.demo',
            'instructor@mkscholars.demo',
            'mentor@mkscholars.demo',
            'student@mkscholars.demo',
        ])->count());

        $this->assertSame(4, Academy::query()->whereIn('slug', [
            'technology-coding',
            'language-communication',
            'exam-preparation',
            'career-digital-skills',
        ])->count());

        $demoCourseSlugs = [
            'web-development-foundations',
            'javascript-for-beginners',
            'digital-literacy-essentials',
            'english-communication-for-students',
            'exam-preparation-strategy',
            'cyber-safety-basics',
            'career-readiness-portfolio-skills',
        ];

        $this->assertSame(7, Course::query()->whereIn('slug', $demoCourseSlugs)->count());

        $instructor = User::query()->where('email', 'instructor@mkscholars.demo')->firstOrFail();
        $student = User::query()->where('email', 'student@mkscholars.demo')->firstOrFail();

        $this->assertSame(7, Course::query()->whereIn('slug', $demoCourseSlugs)->where('instructor_id', $instructor->id)->count());
        $this->assertGreaterThanOrEqual(21, Module::query()->whereHas('course', fn ($query) => $query->whereIn('slug', $demoCourseSlugs))->count());
        $this->assertGreaterThanOrEqual(63, Lesson::query()->whereHas('module.course', fn ($query) => $query->whereIn('slug', $demoCourseSlugs))->count());
        $this->assertGreaterThanOrEqual(7, Quiz::query()->whereHas('lesson.module.course', fn ($query) => $query->whereIn('slug', $demoCourseSlugs))->count());
        $this->assertGreaterThanOrEqual(7, Assignment::query()->whereHas('lesson.module.course', fn ($query) => $query->whereIn('slug', $demoCourseSlugs))->count());
        $this->assertGreaterThanOrEqual(4, Enrollment::query()->where('user_id', $student->id)->count());
        $this->assertGreaterThanOrEqual(1, MentorAssignment::query()->where('student_id', $student->id)->count());
        $this->assertGreaterThanOrEqual(11, AppNotification::query()->whereIn('user_id', User::query()->whereLike('email', '%@mkscholars.demo')->pluck('id'))->count());
    }

    public function test_demo_pitch_seeder_uses_local_storage_backed_images(): void
    {
        $this->seed(DemoPitchSeeder::class);

        $this->assertDatabaseHas('academies', [
            'slug' => 'technology-coding',
            'image_path' => 'demo/academy-technology.webp',
        ]);
        $this->assertDatabaseHas('courses', [
            'slug' => 'web-development-foundations',
            'featured_image_path' => 'demo/course-web-development.webp',
        ]);
    }
}
