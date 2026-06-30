<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Models\AppNotification;
use App\Models\Assignment;
use App\Models\AssignmentQuestion;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\MentorAssignment;
use App\Models\MentorCheckIn;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoPitchSeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoPitchSeeder skipped in production.');

            return;
        }

        $this->ensureDemoImages();

        $admin = $this->user('MK Scholars Admin', 'admin@mkscholars.demo', User::ROLE_ADMIN);
        $instructor = $this->user('Aline Demo Instructor', 'instructor@mkscholars.demo', User::ROLE_INSTRUCTOR);
        $mentor = $this->user('Patrick Demo Mentor', 'mentor@mkscholars.demo', User::ROLE_MENTOR);
        $student = $this->user('Grace Demo Student', 'student@mkscholars.demo', User::ROLE_STUDENT);

        $academies = $this->academies();
        $courses = $this->courses($academies, $instructor);

        foreach ($courses as $course) {
            $this->content($course);
        }

        $this->enrollStudent($student, $courses);
        $this->mentor($mentor, $student, reset($courses));
        $this->notifications($admin, $instructor, $mentor, $student);

        $this->command?->info('Demo pitch data seeded. Local-only demo password: '.self::PASSWORD);
    }

    private function user(string $name, string $email, string $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'role' => $role,
                'approval_status' => User::APPROVAL_APPROVED,
                'approved_at' => now(),
            ],
        );
    }

    /** @return array<string, Academy> */
    private function academies(): array
    {
        $items = [
            'technology-coding' => ['Technology & Coding Academy', 'Practical software, web, and digital technology skills.', 'Build confidence with web development, JavaScript, cybersecurity basics, and practical projects.', Academy::ICON_CODE, 'demo/academy-technology.webp'],
            'language-communication' => ['Language & Communication Academy', 'Academic English, public speaking, and professional communication.', 'Improve written and spoken communication for school, work, scholarships, and interviews.', Academy::ICON_LANGUAGE, 'demo/academy-language.webp'],
            'exam-preparation' => ['Exam Preparation Academy', 'Revision systems, test strategy, and performance habits.', 'Prepare for exams with structured study plans, practice routines, and confidence-building methods.', Academy::ICON_TARGET, 'demo/academy-exams.webp'],
            'career-digital-skills' => ['Career & Digital Skills Academy', 'Career readiness, portfolios, productivity, and digital confidence.', 'Develop job-ready habits, portfolio evidence, online safety, and workplace-ready digital skills.', Academy::ICON_BRIEFCASE, 'demo/academy-career.webp'],
        ];

        $academies = [];

        foreach ($items as $slug => [$name, $summary, $description, $icon, $image]) {
            $academies[$slug] = Academy::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'summary' => $summary,
                    'description' => $description,
                    'icon' => $icon,
                    'image_path' => $image,
                    'status' => Academy::STATUS_PUBLISHED,
                ],
            );
        }

        return $academies;
    }

    /** @return array<string, Course> */
    private function courses(array $academies, User $instructor): array
    {
        $items = [
            ['technology-coding', 'web-development-foundations', 'Web Development Foundations', 'Learn HTML, CSS, responsive layouts, and the foundations of building real websites.', 'Beginner', '6 weeks', true, null, 'demo/course-web-development.webp'],
            ['technology-coding', 'javascript-for-beginners', 'JavaScript for Beginners', 'Understand variables, functions, DOM events, and simple interactive web projects.', 'Beginner', '5 weeks', false, 35000, 'demo/course-javascript-beginners.webp'],
            ['career-digital-skills', 'digital-literacy-essentials', 'Digital Literacy Essentials', 'Master safe, confident use of documents, email, research, storage, and productivity tools.', 'Beginner', '4 weeks', true, null, 'demo/course-digital-literacy.webp'],
            ['language-communication', 'english-communication-for-students', 'English Communication for Students', 'Practice academic speaking, clear writing, presentations, and everyday confidence.', 'Intermediate', '6 weeks', false, 30000, 'demo/course-english-communication.webp'],
            ['exam-preparation', 'exam-preparation-strategy', 'Exam Preparation Strategy', 'Build a focused revision plan, practice habits, and exam-day decision routines.', 'All levels', '3 weeks', true, null, 'demo/course-exam-strategy.webp'],
            ['career-digital-skills', 'cyber-safety-basics', 'Cyber Safety Basics', 'Protect accounts, recognize scams, manage passwords, and browse safely as a student.', 'Beginner', '2 weeks', true, null, 'demo/course-cyber-basics.webp'],
            ['career-digital-skills', 'career-readiness-portfolio-skills', 'Career Readiness and Portfolio Skills', 'Prepare your profile, project evidence, interview story, and portfolio presentation.', 'Intermediate', '5 weeks', false, 45000, 'demo/course-career-portfolio.webp'],
        ];

        $courses = [];

        foreach ($items as [$academyKey, $slug, $title, $short, $level, $duration, $free, $price, $image]) {
            $courses[$slug] = Course::updateOrCreate(
                ['slug' => $slug],
                [
                    'academy_id' => $academies[$academyKey]->id,
                    'instructor_id' => $instructor->id,
                    'title' => $title,
                    'short_description' => $short,
                    'full_description' => $short.' This demo course includes realistic modules, guided lessons, a quiz, an assignment, and dashboard-ready progress data for presentations.',
                    'level' => $level,
                    'duration' => $duration,
                    'price' => $price,
                    'is_free' => $free,
                    'price_amount' => $price,
                    'currency' => 'RWF',
                    'access_type' => $free ? Course::ACCESS_FREE : Course::ACCESS_PAID,
                    'status' => Course::STATUS_PUBLISHED,
                    'featured_image_path' => $image,
                    'learning_outcomes' => ['Follow a clear learning path', 'Complete practical activities', 'Show progress with evidence'],
                ],
            );
        }

        return $courses;
    }

    private function content(Course $course): void
    {
        $moduleTitles = ['Foundations', 'Guided Practice', 'Project and Review'];

        foreach ($moduleTitles as $moduleIndex => $moduleTitle) {
            $module = Module::updateOrCreate(
                ['course_id' => $course->id, 'slug' => Str::slug($moduleTitle)],
                [
                    'title' => $moduleTitle,
                    'summary' => 'A realistic demo module for '.$course->title.'.',
                    'sort_order' => $moduleIndex + 1,
                    'status' => Course::STATUS_PUBLISHED,
                ],
            );

            for ($lessonIndex = 1; $lessonIndex <= 3; $lessonIndex++) {
                $type = $lessonIndex === 2 ? 'video' : 'text';
                Lesson::updateOrCreate(
                    ['module_id' => $module->id, 'slug' => 'lesson-'.$lessonIndex],
                    [
                        'title' => $moduleTitle.' Lesson '.$lessonIndex,
                        'summary' => 'A focused lesson for demo learners.',
                        'lesson_type' => $type,
                        'video_url' => $type === 'video' ? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' : null,
                        'content' => "This lesson helps students understand {$course->title} through short explanations, examples, and reflection prompts.",
                        'duration_minutes' => 20 + ($lessonIndex * 5),
                        'estimated_minutes' => 20 + ($lessonIndex * 5),
                        'sort_order' => $lessonIndex,
                        'is_free_preview' => $moduleIndex === 0 && $lessonIndex === 1,
                        'status' => Course::STATUS_PUBLISHED,
                    ],
                );
            }
        }

        $firstLesson = Lesson::query()->whereHas('module', fn ($query) => $query->where('course_id', $course->id))->orderBy('id')->first();
        $lastLesson = Lesson::query()->whereHas('module', fn ($query) => $query->where('course_id', $course->id))->orderByDesc('id')->first();

        if ($firstLesson) {
            $this->quiz($firstLesson, $course);
        }

        if ($lastLesson) {
            $this->assignment($lastLesson, $course);
        }
    }

    private function quiz(Lesson $lesson, Course $course): Quiz
    {
        $quiz = Quiz::updateOrCreate(
            ['lesson_id' => $lesson->id, 'title' => $course->title.' Knowledge Check'],
            ['description' => 'A short checkpoint quiz for the demo course.', 'passing_score' => 60, 'max_attempts' => 3, 'status' => Quiz::STATUS_PUBLISHED],
        );

        $questions = [
            ['What is the best first step in a structured course?', ['Review the goal', 'Skip the outline', 'Avoid practice'], 0],
            ['Why are practice activities useful?', ['They create evidence of learning', 'They replace all lessons', 'They hide mistakes'], 0],
            ['What should a student track weekly?', ['Progress and blockers', 'Only final scores', 'Nothing'], 0],
        ];

        foreach ($questions as $index => [$text, $options, $correct]) {
            $question = QuizQuestion::updateOrCreate(
                ['quiz_id' => $quiz->id, 'question_text' => $text],
                ['question_type' => QuizQuestion::TYPE_MULTIPLE_CHOICE, 'points' => 1, 'sort_order' => $index + 1, 'status' => QuizQuestion::STATUS_PUBLISHED],
            );

            foreach ($options as $optionIndex => $optionText) {
                QuizOption::updateOrCreate(
                    ['quiz_question_id' => $question->id, 'sort_order' => $optionIndex + 1],
                    ['option_text' => $optionText, 'is_correct' => $optionIndex === $correct],
                );
            }
        }

        return $quiz;
    }

    private function assignment(Lesson $lesson, Course $course): Assignment
    {
        $assignment = Assignment::updateOrCreate(
            ['lesson_id' => $lesson->id, 'title' => $course->title.' Reflection Task'],
            [
                'instructions' => 'Write a short reflection explaining what you learned, what you practiced, and what you will improve next.',
                'submission_type' => Assignment::TYPE_MIXED,
                'max_score' => 100,
                'due_days_after_enrollment' => 7,
                'allow_late_submission' => true,
                'status' => Assignment::STATUS_PUBLISHED,
            ],
        );

        foreach (['What did you learn?', 'What evidence can you show?', 'What will you improve next?'] as $index => $question) {
            AssignmentQuestion::updateOrCreate(
                ['assignment_id' => $assignment->id, 'question_text' => $question],
                ['question_type' => AssignmentQuestion::TYPE_TEXTAREA, 'points' => 10, 'sort_order' => $index + 1, 'is_required' => true],
            );
        }

        return $assignment;
    }

    private function enrollStudent(User $student, array $courses): void
    {
        foreach (array_slice($courses, 0, 4) as $course) {
            Enrollment::updateOrCreate(
                ['user_id' => $student->id, 'course_id' => $course->id],
                ['status' => Enrollment::STATUS_ACTIVE, 'enrolled_at' => now()->subDays(10)],
            );

            $lessons = Lesson::query()->whereHas('module', fn ($query) => $query->where('course_id', $course->id))->orderBy('id')->take(4)->get();
            foreach ($lessons as $index => $lesson) {
                LessonProgress::updateOrCreate(
                    ['user_id' => $student->id, 'course_id' => $course->id, 'lesson_id' => $lesson->id],
                    ['status' => $index < 3 ? LessonProgress::STATUS_COMPLETED : LessonProgress::STATUS_IN_PROGRESS, 'progress_percent' => $index < 3 ? 100 : 40, 'started_at' => now()->subDays(8), 'completed_at' => $index < 3 ? now()->subDays(4 - $index) : null],
                );
            }
        }

        $course = reset($courses);
        $quiz = Quiz::query()->whereHas('lesson.module', fn ($query) => $query->where('course_id', $course->id))->first();
        if ($quiz) {
            $attempt = QuizAttempt::updateOrCreate(
                ['quiz_id' => $quiz->id, 'user_id' => $student->id],
                ['score' => 3, 'total_points' => 3, 'percentage' => 100, 'status' => QuizAttempt::STATUS_PASSED, 'started_at' => now()->subDays(3), 'submitted_at' => now()->subDays(3)],
            );

            foreach ($quiz->questions()->with('options')->get() as $question) {
                $option = $question->options->firstWhere('is_correct', true);
                if ($option) {
                    QuizAnswer::updateOrCreate(
                        ['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $question->id],
                        ['quiz_option_id' => $option->id, 'is_correct' => true, 'points_awarded' => 1],
                    );
                }
            }
        }

        $assignment = Assignment::query()->whereHas('lesson.module', fn ($query) => $query->where('course_id', $course->id))->first();
        if ($assignment) {
            AssignmentSubmission::updateOrCreate(
                ['assignment_id' => $assignment->id, 'user_id' => $student->id],
                ['text_answer' => 'Demo reflection: I completed the lesson, practiced the core skill, and identified my next improvement step.', 'status' => AssignmentSubmission::STATUS_SUBMITTED, 'submitted_at' => now()->subDays(2)],
            );
        }
    }

    private function mentor(User $mentor, User $student, Course $course): void
    {
        $assignment = MentorAssignment::updateOrCreate(
            ['mentor_id' => $mentor->id, 'student_id' => $student->id, 'course_id' => $course->id],
            ['status' => MentorAssignment::STATUS_ACTIVE, 'assigned_at' => now()->subDays(7), 'notes' => 'Demo mentorship assignment for pitch walkthrough.'],
        );

        MentorCheckIn::updateOrCreate(
            ['mentor_assignment_id' => $assignment->id, 'topic' => 'Weekly progress review'],
            ['scheduled_at' => now()->addDays(2), 'status' => MentorCheckIn::STATUS_SCHEDULED, 'student_notes' => 'Need support choosing next practice task.'],
        );
    }

    private function notifications(User $admin, User $instructor, User $mentor, User $student): void
    {
        $items = [
            [$student, 'New lesson available', 'Continue your Web Development Foundations learning path.', AppNotification::TYPE_INFO, AppNotification::CATEGORY_COURSE, route('student.my-courses')],
            [$student, 'Assignment submitted successfully', 'Your reflection task is ready for instructor review.', AppNotification::TYPE_SUCCESS, AppNotification::CATEGORY_ASSIGNMENT, route('student.assignments')],
            [$student, 'Certificate progress updated', 'You are making progress toward certificate eligibility.', AppNotification::TYPE_REMINDER, AppNotification::CATEGORY_CERTIFICATE, route('student.certificates')],
            [$instructor, 'New assignment submission', 'Grace Demo Student submitted a course reflection.', AppNotification::TYPE_REMINDER, AppNotification::CATEGORY_ASSIGNMENT, route('instructor.dashboard')],
            [$instructor, 'Quiz attempt received', 'A student completed a demo knowledge check.', AppNotification::TYPE_INFO, AppNotification::CATEGORY_QUIZ, route('instructor.dashboard')],
            [$instructor, 'Student joined your course', 'A demo learner enrolled in your owned course.', AppNotification::TYPE_SUCCESS, AppNotification::CATEGORY_COURSE, route('instructor.courses.index')],
            [$mentor, 'Student check-in due', 'A weekly progress review is scheduled soon.', AppNotification::TYPE_REMINDER, AppNotification::CATEGORY_MENTORSHIP, route('mentor.check-ins')],
            [$mentor, 'Progress review needed', 'Review your assigned student progress before the next check-in.', AppNotification::TYPE_INFO, AppNotification::CATEGORY_MENTORSHIP, route('mentor.students')],
            [$admin, 'New instructor course awaiting review', 'Demo instructor-owned courses are ready for admin supervision.', AppNotification::TYPE_WARNING, AppNotification::CATEGORY_COURSE, '/admin/courses'],
            [$admin, 'Payment review pending', 'Demo manual payment review workflow is ready for presentation.', AppNotification::TYPE_REMINDER, AppNotification::CATEGORY_PAYMENT, '/admin/payments'],
            [$admin, 'System demo content ready', 'Pitch academies, courses, users, and notifications have been seeded.', AppNotification::TYPE_SUCCESS, AppNotification::CATEGORY_SYSTEM, '/admin'],
        ];

        foreach ($items as [$user, $title, $message, $type, $category, $url]) {
            AppNotification::updateOrCreate(
                ['user_id' => $user->id, 'title' => $title, 'category' => $category],
                ['role' => $user->role, 'message' => $message, 'type' => $type, 'action_url' => $url, 'read_at' => null, 'expires_at' => null, 'created_by' => null],
            );
        }
    }

    private function ensureDemoImages(): void
    {
        $mapping = [
            'demo/academy-technology.webp' => public_path('images/demo/academy-technology.webp'),
            'demo/academy-language.webp' => public_path('images/demo/academy-language.webp'),
            'demo/academy-exams.webp' => public_path('images/demo/academy-exams.webp'),
            'demo/academy-career.webp' => public_path('images/demo/academy-career.webp'),
            'demo/course-web-development.webp' => public_path('images/demo/course-web-development.webp'),
            'demo/course-javascript-beginners.webp' => public_path('images/demo/course-javascript-beginners.webp'),
            'demo/course-digital-literacy.webp' => public_path('images/demo/course-digital-literacy.webp'),
            'demo/course-english-communication.webp' => public_path('images/demo/course-english-communication.webp'),
            'demo/course-exam-strategy.webp' => public_path('images/demo/course-exam-strategy.webp'),
            'demo/course-cyber-basics.webp' => public_path('images/demo/course-cyber-basics.webp'),
            'demo/course-career-portfolio.webp' => public_path('images/demo/course-career-portfolio.webp'),
        ];

        foreach ($mapping as $storagePath => $source) {
            if (is_file($source) && ! Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->put($storagePath, file_get_contents($source));
            }
        }
    }
}
