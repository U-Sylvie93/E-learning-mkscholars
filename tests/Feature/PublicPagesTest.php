<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_public_pages_load(): void
    {
        foreach (['/', '/academies', '/courses', '/opportunities', '/pricing', '/about', '/contact'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }


    public function test_homepage_no_longer_promotes_opportunities_and_uses_branded_footer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Build your future')
            ->assertSee('bg-mk-navy', false)
            ->assertDontSee('Scholarships, events, and career openings')
            ->assertDontSee('Find Scholarships')
            ->assertDontSee('x-opportunity-card');
    }
    public function test_course_details_page_loads_from_placeholder_slug(): void
    {
        $this->get('/courses/academic-english-mastery')
            ->assertOk()
            ->assertSee('Academic English Mastery');
    }
}

