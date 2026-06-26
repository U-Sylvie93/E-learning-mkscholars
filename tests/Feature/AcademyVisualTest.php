<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcademyVisualTest extends TestCase
{
    use RefreshDatabase;

    public function test_academies_page_displays_uploaded_image_and_selected_icon(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('academies/coding.webp', 'demo-image');

        Academy::create([
            'name' => 'Coding Visual Academy',
            'slug' => 'coding-visual-academy',
            'summary' => 'Visual academy card with uploaded image and safe icon.',
            'description' => 'Demo visual academy.',
            'icon' => Academy::ICON_CODE,
            'image_path' => 'academies/coding.webp',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $this->get(route('academies'))
            ->assertOk()
            ->assertSee('Coding Visual Academy')
            ->assertSee('Code')
            ->assertSee('/storage/academies/coding.webp', false);
    }

    public function test_academies_page_uses_local_fallback_without_image_or_icon(): void
    {
        Academy::create([
            'name' => 'Fallback Academy',
            'slug' => 'fallback-academy',
            'summary' => 'Academy card without optional image or icon.',
            'description' => null,
            'icon' => null,
            'image_path' => null,
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $this->get(route('academies'))
            ->assertOk()
            ->assertSee('Fallback Academy')
            ->assertSee('Book Open')
            ->assertSee('bg-[radial-gradient', false)
            ->assertDontSee('images.unsplash.com', false)
            ->assertDontSee('<img class="h-full w-full object-cover', false);
    }

    public function test_invalid_academy_icon_falls_back_to_default(): void
    {
        Academy::create([
            'name' => 'Invalid Icon Academy',
            'slug' => 'invalid-icon-academy',
            'summary' => 'Academy card with unsafe icon data.',
            'description' => null,
            'icon' => '<script>alert(1)</script>',
            'image_path' => null,
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $this->get(route('academies'))
            ->assertOk()
            ->assertSee('Invalid Icon Academy')
            ->assertSee('Book Open')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_course_pages_render_academy_icon_data_safely(): void
    {
        $academy = Academy::create([
            'name' => 'Language Visual Academy',
            'slug' => 'language-visual-academy',
            'summary' => 'Language academy visual test.',
            'description' => null,
            'icon' => Academy::ICON_LANGUAGE,
            'image_path' => null,
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id,
            'title' => 'Language Visual Course',
            'slug' => 'language-visual-course',
            'short_description' => 'A course card with academy icon data.',
            'full_description' => 'A public course detail with academy icon data.',
            'level' => 'Beginner',
            'duration' => '2 weeks',
            'price' => 0,
            'is_free' => true,
            'price_amount' => null,
            'currency' => 'RWF',
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
            'featured_image_path' => null,
            'learning_outcomes' => ['Practice clear communication'],
        ]);

        $this->get(route('courses'))
            ->assertOk()
            ->assertSee('Language Visual Academy')
            ->assertSee('Language Visual Course');

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee('Language Visual Academy')
            ->assertSee('Language');
    }

    public function test_course_pages_load_when_academy_has_no_image_or_icon(): void
    {
        $academy = Academy::create([
            'name' => 'Plain Academy',
            'slug' => 'plain-academy',
            'summary' => 'Academy without image or icon.',
            'description' => null,
            'icon' => null,
            'image_path' => null,
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $course = Course::create([
            'academy_id' => $academy->id,
            'title' => 'Plain Academy Course',
            'slug' => 'plain-academy-course',
            'short_description' => 'Course with plain academy relation.',
            'full_description' => 'Course detail still renders when academy visual fields are missing.',
            'level' => 'Beginner',
            'duration' => '1 week',
            'price' => 0,
            'is_free' => true,
            'price_amount' => null,
            'currency' => 'RWF',
            'access_type' => Course::ACCESS_FREE,
            'status' => Course::STATUS_PUBLISHED,
            'featured_image_path' => null,
            'learning_outcomes' => [],
        ]);

        $this->get(route('courses'))->assertOk()->assertSee('Plain Academy Course');

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee('Plain Academy')
            ->assertSee('Book Open');
    }

    public function test_admin_academy_resource_route_exists(): void
    {
        $this->assertTrue(Route::has('filament.admin.resources.academies.index'));
    }
}
