<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_load(): void
    {
        foreach (['/', '/academies', '/courses', '/pricing', '/about', '/contact'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_public_navbar_uses_clean_order(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Home', 'About', 'Academies', 'Courses', 'Contact'])
            ->assertDontSee('Pricing')
            ->assertDontSee('Opportunities')
            ->assertDontSee('Premium learning support');
    }
    public function test_academies_page_uses_premium_listing_layout(): void
    {
        $this->get('/academies')
            ->assertOk()
            ->assertSee('Choose your academy pathway')
            ->assertSee('data-testid="academies-hero"', false)
            ->assertSee('data-testid="academies-trust-strip"', false)
            ->assertSee('data-testid="academies-grid"', false)
            ->assertSee('data-testid="academy-card"', false)
            ->assertSee('images/marketing/academy-learning.webp', false)
            ->assertDontSee('images.unsplash.com', false);
    }

    public function test_homepage_uses_real_education_images_phase_37g(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Learn with structure. Grow with support. Show your progress')
            ->assertSee('data-testid="home-hero"', false)
            ->assertSee('data-testid="home-real-image-visual"', false)
            ->assertSee('images/marketing/hero-learning.webp', false)
            ->assertSee('data-testid="home-premium-image-cards"', false)
            ->assertSee('images/marketing/academy-learning.webp', false)
            ->assertSee('images/marketing/practical-courses.webp', false)
            ->assertSee('images/marketing/student-support.webp', false)
            ->assertSee('data-testid="home-stats-belt"', false)
            ->assertSee('data-testid="animated-counter"', false)
            ->assertSee('Students supported')
            ->assertSee('Support pillars')
            ->assertDontSee('Mentorship')
            ->assertDontSee('mentorship')
            ->assertDontSee('Student workspace')
            ->assertDontSee('Learning path')
            ->assertDontSee('Plan. Learn. Prove progress')
            ->assertDontSee('Course progress')
            ->assertDontSee('restaurant')
            ->assertDontSee('food')
            ->assertDontSee('bakery')
            ->assertDontSee('images.unsplash.com', false);
    }

    public function test_about_page_renders_real_image_story_content(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('Built for disciplined, hopeful, student-centered learning')
            ->assertSee('data-testid="about-story-section"', false)
            ->assertSee('Who we are')
            ->assertSee('What MK Scholars does')
            ->assertSee('How students learn')
            ->assertSee('Academy-based learning')
            ->assertSee('Practical skills and courses')
            ->assertSee('Student learning support')
            ->assertSee('Certificates and verified progress')
            ->assertSee('Our learning promise')
            ->assertSee('images/marketing/about-learning.webp', false)
            ->assertDontSee('images.unsplash.com', false);
    }

    public function test_courses_page_uses_real_image_hero_and_benefit_cards(): void
    {
        $this->get('/courses')
            ->assertOk()
            ->assertSee('Explore practical courses built for progress')
            ->assertSee('data-testid="courses-hero"', false)
            ->assertSee('images/marketing/courses-hero.webp', false)
            ->assertSee('Browse Courses')
            ->assertSee('data-testid="courses-trust-strip"', false)
            ->assertSee('data-testid="courses-grid"', false)
            ->assertSee('data-testid="course-card"', false)
            ->assertSee('Verified proof')
            ->assertSee('Class support')
            ->assertDontSee('images.unsplash.com', false);
    }

    public function test_contact_page_has_education_support_image_trust_cards_and_links(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('Start a learning conversation with MK Scholars')
            ->assertSee('images/marketing/contact-support.webp', false)
            ->assertSee('data-testid="contact-trust-cards"', false)
            ->assertSee('Phone')
            ->assertSee('+250798611161')
            ->assertSee('tel:+250798611161', false)
            ->assertSee('Email')
            ->assertSee('mkscholars250@gmail.com')
            ->assertSee('mailto:mkscholars250@gmail.com', false)
            ->assertSee('Location')
            ->assertSee('Kigali, Rwanda - Kicukiro')
            ->assertDontSee('hello@mkscholars.example')
            ->assertDontSee('restaurant')
            ->assertDontSee('food')
            ->assertDontSee('bakery');
    }

    public function test_footer_has_compact_support_cards_subscribe_area_and_social_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Verified certificates')
            ->assertSee('Weekly check-ins')
            ->assertSee('Live class schedule')
            ->assertSee('Progress tracker')
            ->assertSee('Subscribe')
            ->assertSee('https://www.youtube.com/@mkscholars', false)
            ->assertSee('https://www.whatsapp.com/channel/0029VbBFZSt8vd1L1hSex31Z', false)
            ->assertSee('https://www.facebook.com/people/MK-Scholars/100069262368212/?sk=following', false)
            ->assertSee('https://x.com/MkScholars', false)
            ->assertSee('https://www.instagram.com/accounts/login/?next=%2Fmkscholars_', false)
            ->assertSee('Visit MK Scholars on YouTube')
            ->assertSee('Visit MK Scholars on WhatsApp')
            ->assertSee('Visit MK Scholars on Instagram')
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('Premium learning support');
    }

    public function test_opportunity_public_routes_are_removed(): void
    {
        $this->get('/opportunities')->assertNotFound();
        $this->get('/opportunities/demo')->assertNotFound();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Opportunities')
            ->assertDontSee('/opportunities', false);
    }

    public function test_course_details_page_loads_from_placeholder_slug(): void
    {
        $this->get('/courses/academic-english-mastery')
            ->assertOk()
            ->assertSee('Academic English Mastery')
            ->assertDontSee('images.unsplash.com', false);
    }

    public function test_auth_pages_use_premium_layout_and_keep_required_fields(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-testid="auth-login-page"', false)
            ->assertSee('Welcome back to your learning space')
            ->assertSee('Sign in')
            ->assertSee('Email address')
            ->assertSee('Password')
            ->assertSee('Keep me signed in');

        $this->get('/register')
            ->assertOk()
            ->assertSee('data-testid="auth-register-page"', false)
            ->assertSee('Create your MK Scholars account')
            ->assertSee('Full name')
            ->assertSee('Email address')
            ->assertSee('I am joining as')
            ->assertSee('Confirm password')
            ->assertDontSee('Mentor');

        $this->get('/setup-admin')
            ->assertOk()
            ->assertSee('data-testid="auth-setup-admin-page"', false)
            ->assertSee('First admin setup')
            ->assertSee('first administrator');
    }

    public function test_auth_blades_do_not_include_hardcoded_credentials(): void
    {
        foreach ([
            resource_path('views/auth/login.blade.php'),
            resource_path('views/auth/register.blade.php'),
            resource_path('views/auth/setup-admin.blade.php'),
            resource_path('views/livewire/login-form.blade.php'),
            resource_path('views/livewire/register-form.blade.php'),
            resource_path('views/livewire/setup-admin-form.blade.php'),
        ] as $path) {
            $contents = file_get_contents($path);

            $this->assertStringNotContainsString('you@example.com', $contents);
            $this->assertStringNotContainsString('admin@example.com', $contents);
            $this->assertStringNotContainsString('password123', strtolower($contents));
        }
    }
}




