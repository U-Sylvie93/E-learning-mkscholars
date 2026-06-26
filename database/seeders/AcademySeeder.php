<?php

namespace Database\Seeders;

use App\Models\Academy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AcademySeeder extends Seeder
{
    public function run(): void
    {
        $academies = [
            [
                'name' => 'Coding & Tech Academy',
                'summary' => 'Practical coding, digital literacy, and technology skills for future-ready learners.',
                'description' => 'A focused academy for students building confidence in programming, web foundations, digital tools, and practical problem solving.',
                'icon' => 'code',
            ],
            [
                'name' => 'Language Academy',
                'summary' => 'Communication, academic English, and language confidence for school and career growth.',
                'description' => 'A student-centered language pathway covering reading, writing, speaking, presentation, and academic communication skills.',
                'icon' => 'language',
            ],
            [
                'name' => 'Test Preparation Academy',
                'summary' => 'Structured preparation for exams, assessments, and measurable academic progress.',
                'description' => 'A preparation academy for learners who need practice plans, exam strategies, revision systems, and performance coaching.',
                'icon' => 'target',
            ],
            [
                'name' => 'Interview & Career Academy',
                'summary' => 'Career readiness, interview confidence, and professional communication foundations.',
                'description' => 'A practical academy for students preparing for interviews, internships, early careers, and professional opportunities.',
                'icon' => 'briefcase',
            ],
            [
                'name' => 'Scholarship & Study Abroad Academy',
                'summary' => 'Guidance for applications, essays, scholarships, and international study readiness.',
                'description' => 'A pathway for learners preparing strong profiles, personal statements, scholarship applications, and study abroad plans.',
                'icon' => 'graduation-cap',
            ],
        ];

        foreach ($academies as $academy) {
            Academy::updateOrCreate(
                ['slug' => Str::slug($academy['name'])],
                [
                    ...$academy,
                    'slug' => Str::slug($academy['name']),
                    'status' => Academy::STATUS_PUBLISHED,
                ],
            );
        }
    }
}
