<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonActivity;
use App\Models\LiveClass;
use App\Models\Module;
use App\Models\Opportunity;
use App\Models\OpportunityRequirement;
use App\Models\PaymentMethod;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoSeeder skipped in production.');

            return;
        }

        $admin = $this->user('MK Admin', 'admin@mkscholars.test', User::ROLE_ADMIN);
        $this->user('Demo Student', 'student@mkscholars.test', User::ROLE_STUDENT);
        $instructor = $this->user('Demo Instructor', 'instructor@mkscholars.test', User::ROLE_INSTRUCTOR);
        $this->user('Demo Mentor', 'mentor@mkscholars.test', User::ROLE_MENTOR);

        $academies = $this->academies();

        $courses = [
            [
                'academy' => 'Coding & Tech Academy',
                'title' => 'Web Foundations for Students',
                'short_description' => 'Build a confident foundation in HTML, CSS, JavaScript, and practical web thinking.',
                'full_description' => 'A student-friendly introduction to modern web development, project planning, responsive layouts, and portfolio-ready practice.',
                'level' => 'Beginner',
                'duration' => '6 weeks',
                'is_free' => true,
                'price_amount' => null,
                'outcomes' => ['Plan simple websites', 'Build responsive pages', 'Explain core web concepts'],
            ],
            [
                'academy' => 'Scholarship & Study Abroad Academy',
                'title' => 'Scholarship Application Bootcamp',
                'short_description' => 'Prepare essays, documents, timelines, and a stronger scholarship application profile.',
                'full_description' => 'A guided pathway for students preparing personal statements, recommendation planning, document checklists, and application trackers.',
                'level' => 'Intermediate',
                'duration' => '4 weeks',
                'is_free' => false,
                'price_amount' => 45000,
                'outcomes' => ['Draft a personal statement', 'Organize application documents', 'Build a deadline plan'],
            ],
            [
                'academy' => 'Interview & Career Academy',
                'title' => 'Interview Confidence Lab',
                'short_description' => 'Practice clear answers, professional introductions, and student career readiness.',
                'full_description' => 'A practical interview readiness course with communication drills, feedback prompts, and mentor-guided practice.',
                'level' => 'Beginner',
                'duration' => '3 weeks',
                'is_free' => false,
                'price_amount' => 30000,
                'outcomes' => ['Prepare interview answers', 'Introduce yourself confidently', 'Reflect on feedback'],
            ],
        ];

        foreach ($courses as $index => $courseData) {
            $course = $this->course($academies[$courseData['academy']], $courseData);
            $module = $this->module($course, 'Getting Started', 1);
            $lesson = $this->lesson($module, 'Welcome and Learning Plan', 1, 'text', true);

            $this->activity($lesson, 'Read the course guide', 'download', 1);
            $this->activity($lesson, 'Introduce your goal', 'discussion', 2);

            $practiceLesson = $this->lesson($module, 'Guided Practice', 2, $index === 0 ? 'video' : 'assignment', false);
            $this->activity($practiceLesson, 'Complete the practice reflection', 'assignment', 1);

            if ($index === 0) {
                $this->quiz($lesson);
            }

            if ($index === 1) {
                $this->assignment($practiceLesson);
            }
        }

        $demoPlan = SubscriptionPlan::updateOrCreate(
            ['slug' => 'demo-scholar-access'],
            [
                'name' => 'Demo Scholar Access',
                'description' => 'A manual subscription plan for testing bundled course access.',
                'price_amount' => 75000,
                'currency' => 'RWF',
                'billing_cycle' => SubscriptionPlan::BILLING_MONTHLY,
                'duration_days' => 30,
                'status' => SubscriptionPlan::STATUS_ACTIVE,
                'features' => ['Access included paid courses', 'Manual payment review', 'Student dashboard tracking'],
            ],
        );

        $demoPlan->courses()->syncWithoutDetaching(
            Course::query()
                ->whereIn('slug', ['scholarship-application-bootcamp', 'interview-confidence-lab'])
                ->pluck('id')
                ->all(),
        );

        $firstCourse = Course::where('slug', 'web-foundations-for-students')->first();

        if ($firstCourse) {
            LiveClass::updateOrCreate(
                ['title' => 'Demo Live Orientation', 'course_id' => $firstCourse->id],
                [
                    'module_id' => null,
                    'lesson_id' => null,
                    'instructor_id' => $instructor->id,
                    'description' => 'A short orientation session for demo testing.',
                    'meeting_url' => 'https://meet.google.com/demo-mk-scholars',
                    'platform' => LiveClass::PLATFORM_GOOGLE_MEET,
                    'starts_at' => now()->addDays(3)->setTime(16, 0),
                    'ends_at' => now()->addDays(3)->setTime(17, 0),
                    'status' => LiveClass::STATUS_SCHEDULED,
                    'recording_url' => null,
                ],
            );
        }

        $opportunity = Opportunity::updateOrCreate(
            ['slug' => 'mk-scholars-demo-scholarship'],
            [
                'title' => 'MK Scholars Demo Scholarship',
                'type' => Opportunity::TYPE_SCHOLARSHIP,
                'organization' => 'MK Scholars',
                'country' => 'Rwanda',
                'city' => 'Kigali',
                'description' => 'A demo scholarship opportunity for testing the public opportunity page and student application tracker.',
                'requirements' => 'Submit a CV, transcript, and short motivation letter.',
                'benefits' => 'Partial tuition support and mentorship guidance.',
                'application_url' => null,
                'deadline' => now()->addDays(21)->toDateString(),
                'status' => Opportunity::STATUS_PUBLISHED,
                'is_featured' => true,
                'created_by' => $admin->id,
            ],
        );

        foreach (['CV', 'Academic Transcript', 'Motivation Letter'] as $order => $name) {
            OpportunityRequirement::updateOrCreate(
                ['opportunity_id' => $opportunity->id, 'name' => $name],
                [
                    'description' => 'Demo requirement for application testing.',
                    'is_required' => true,
                    'sort_order' => $order + 1,
                ],
            );
        }

        PaymentMethod::updateOrCreate(
            ['name' => 'Demo Mobile Money'],
            [
                'type' => PaymentMethod::TYPE_MOMO,
                'account_name' => 'MK Scholars Demo',
                'account_number' => '+250 780 000 000',
                'instructions' => 'Local demo only: upload a PDF or image as payment proof for admin review.',
                'status' => PaymentMethod::STATUS_ACTIVE,
            ],
        );

        $this->command?->info('MK Scholars demo data seeded. Local password for all demo users: password');

    }

    private function user(string $name, string $email, string $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
            ],
        );
    }

    /**
     * @return array<string, Academy>
     */
    private function academies(): array
    {
        $items = [
            'Coding & Tech Academy' => ['summary' => 'Practical coding and digital skills.', 'icon' => 'code'],
            'Language Academy' => ['summary' => 'Academic English and communication confidence.', 'icon' => 'language'],
            'Test Preparation Academy' => ['summary' => 'Exam readiness and structured revision.', 'icon' => 'target'],
            'Interview & Career Academy' => ['summary' => 'Career readiness and interview practice.', 'icon' => 'briefcase'],
            'Scholarship & Study Abroad Academy' => ['summary' => 'Scholarships, essays, and study abroad preparation.', 'icon' => 'graduation-cap'],
        ];

        $academies = [];

        foreach ($items as $name => $data) {
            $academies[$name] = Academy::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'summary' => $data['summary'],
                    'description' => $data['summary'].' Demo pathway for launch testing.',
                    'icon' => $data['icon'],
                    'status' => Academy::STATUS_PUBLISHED,
                ],
            );
        }

        return $academies;
    }

    /**
     * @param array{title:string,short_description:string,full_description:string,level:string,duration:string,is_free:bool,price_amount:?int,outcomes:array<int,string>} $data
     */
    private function course(Academy $academy, array $data): Course
    {
        return Course::updateOrCreate(
            ['slug' => Str::slug($data['title'])],
            [
                'academy_id' => $academy->id,
                'title' => $data['title'],
                'short_description' => $data['short_description'],
                'full_description' => $data['full_description'],
                'level' => $data['level'],
                'duration' => $data['duration'],
                'price' => $data['price_amount'],
                'is_free' => $data['is_free'],
                'price_amount' => $data['price_amount'],
                'currency' => 'RWF',
                'access_type' => $data['is_free'] ? Course::ACCESS_FREE : Course::ACCESS_PAID,
                'status' => Course::STATUS_PUBLISHED,
                'featured_image_path' => null,
                'learning_outcomes' => $data['outcomes'],
            ],
        );
    }

    private function module(Course $course, string $title, int $order): Module
    {
        return Module::updateOrCreate(
            ['course_id' => $course->id, 'slug' => Str::slug($title)],
            [
                'title' => $title,
                'summary' => 'Demo module for launch testing.',
                'sort_order' => $order,
                'status' => Course::STATUS_PUBLISHED,
            ],
        );
    }

    private function lesson(Module $module, string $title, int $order, string $type, bool $preview): Lesson
    {
        return Lesson::updateOrCreate(
            ['module_id' => $module->id, 'slug' => Str::slug($title)],
            [
                'title' => $title,
                'summary' => 'A concise demo lesson used for platform testing.',
                'lesson_type' => $type,
                'video_url' => $type === 'video' ? 'https://example.com/demo-video' : null,
                'content' => 'This demo lesson gives testers realistic content without depending on external course material.',
                'duration_minutes' => 20,
                'estimated_minutes' => 20,
                'sort_order' => $order,
                'is_free_preview' => $preview,
                'status' => Course::STATUS_PUBLISHED,
            ],
        );
    }

    private function activity(Lesson $lesson, string $title, string $type, int $order): void
    {
        LessonActivity::updateOrCreate(
            ['lesson_id' => $lesson->id, 'title' => $title],
            [
                'activity_type' => $type,
                'type' => $type,
                'instructions' => 'Complete this demo activity during QA testing.',
                'resource_url' => null,
                'metadata' => null,
                'sort_order' => $order,
                'status' => Course::STATUS_PUBLISHED,
            ],
        );
    }

    private function quiz(Lesson $lesson): void
    {
        $quiz = Quiz::updateOrCreate(
            ['lesson_id' => $lesson->id, 'title' => 'Web Foundations Check'],
            [
                'description' => 'A short demo quiz for testing quiz attempts.',
                'passing_score' => 50,
                'max_attempts' => 3,
                'time_limit_minutes' => null,
                'status' => Quiz::STATUS_PUBLISHED,
            ],
        );

        $question = QuizQuestion::updateOrCreate(
            ['quiz_id' => $quiz->id, 'question_text' => 'Which language is used to style web pages?'],
            [
                'question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'points' => 1,
                'sort_order' => 1,
                'status' => QuizQuestion::STATUS_PUBLISHED,
            ],
        );

        foreach ([['HTML', false], ['CSS', true], ['SQL', false]] as $order => [$text, $correct]) {
            QuizOption::updateOrCreate(
                ['quiz_question_id' => $question->id, 'sort_order' => $order + 1],
                [
                    'option_text' => $text,
                    'is_correct' => $correct,
                ],
            );
        }
    }

    private function assignment(Lesson $lesson): void
    {
        Assignment::updateOrCreate(
            ['lesson_id' => $lesson->id, 'title' => 'Draft a Motivation Paragraph'],
            [
                'instructions' => 'Write a short motivation paragraph for a scholarship application.',
                'submission_type' => Assignment::TYPE_MIXED,
                'max_score' => 100,
                'due_days_after_enrollment' => 7,
                'allow_late_submission' => true,
                'status' => Assignment::STATUS_PUBLISHED,
            ],
        );
    }
}
