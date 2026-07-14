<?php

return [
    'features' => [
        'mentorship_enabled' => env('MK_MENTORSHIP_ENABLED', false),
    ],

    'email_notifications' => [
        'enabled' => env('MK_EMAIL_NOTIFICATIONS_ENABLED', false),
    ],

    // SWAP IMAGES: replace each 'image' URL below with your own uploaded photo.
    // These are free Unsplash placeholders sized for the card aspect ratio.
    'academies' => [
        [
            'name' => 'Junior Scholars Academy',
            'level' => 'Primary',
            'summary' => 'Confidence-building literacy, numeracy, and study habits for young learners.',
            'students' => '240+ learners',
        ],
        [
            'name' => 'STEM Excellence Academy',
            'level' => 'Secondary',
            'summary' => 'Practical math, science, coding, and problem-solving pathways for ambitious students.',
            'students' => '180+ learners',
        ],
        [
            'name' => 'University Prep Academy',
            'level' => 'Advanced',
            'summary' => 'Exam readiness, academic writing, research skills, and scholarship preparation.',
            'students' => '95+ learners',
        ],
    ],

    'courses' => [
        [
            'slug' => 'academic-english-mastery',
            'title' => 'Academic English Mastery',
            'academy' => 'University Prep Academy',
            'level' => 'Intermediate',
            'duration' => '8 weeks',
            'price' => 'Coming soon',
            'summary' => 'Build strong reading, writing, speaking, and presentation skills for school success.',
            'outcomes' => ['Essay structure', 'Presentation confidence', 'Vocabulary growth'],
        ],
        [
            'slug' => 'mathematics-for-excellence',
            'title' => 'Mathematics for Excellence',
            'academy' => 'STEM Excellence Academy',
            'level' => 'Secondary',
            'duration' => '10 weeks',
            'price' => 'Coming soon',
            'summary' => 'Master core math concepts through guided practice, live support, and weekly challenges.',
            'outcomes' => ['Algebra fluency', 'Exam techniques', 'Problem-solving speed'],
        ],
        [
            'slug' => 'digital-skills-foundation',
            'title' => 'Digital Skills Foundation',
            'academy' => 'STEM Excellence Academy',
            'level' => 'Beginner',
            'duration' => '6 weeks',
            'price' => 'Coming soon',
            'summary' => 'A student-friendly introduction to computers, online research, and responsible technology.',
            'outcomes' => ['Digital confidence', 'Research basics', 'Project portfolio'],
        ],
    ],

    'opportunities' => [
        [
            'title' => 'Scholarship Readiness Lab',
            'type' => 'Workshop',
            'deadline' => 'Coming soon',
            'summary' => 'Prepare strong profiles, essays, and interview answers for future applications.',
        ],
        [
            'title' => 'Student Leadership Circle',
            'type' => 'Community',
            'deadline' => 'Open cohort',
            'summary' => 'Practice leadership, communication, and service through guided student projects.',
        ],
        [
            'title' => 'Career Discovery Week',
            'type' => 'Event',
            'deadline' => 'Date to be announced',
            'summary' => 'Explore university paths and careers with mentors across high-growth fields.',
        ],
    ],

    'pricing' => [
        [
            'name' => 'Starter',
            'price' => 'Coming soon',
            'description' => 'For students beginning a focused learning path.',
            'features' => ['Core course access', 'Weekly study plan', 'Progress check-ins'],
        ],
        [
            'name' => 'Scholar',
            'price' => 'Coming soon',
            'description' => 'For learners who want guided support and stronger accountability.',
            'features' => ['Live class support', 'Practice resources', 'Student feedback'],
            'highlighted' => true,
        ],
        [
            'name' => 'Academy',
            'price' => 'Coming soon',
            'description' => 'For families and groups seeking a complete learning experience.',
            'features' => ['Multi-course access', 'Parent updates', 'Priority coaching'],
        ],
    ],
];
