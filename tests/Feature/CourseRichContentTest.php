<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseRichContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_course_detail_renders_safe_rich_overview_content(): void
    {
        $academy = Academy::factory()->create([
            'name' => 'Coding & Tech Academy',
            'slug' => 'coding-tech-academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $course = Course::factory()->create([
            'academy_id' => $academy->id,
            'title' => 'Rich Overview Course',
            'slug' => 'rich-overview-course',
            'status' => Course::STATUS_PUBLISHED,
            'full_description' => <<<'MARKDOWN'
# Course Overview

Build practical skills with [MK Scholars](https://example.com).

- Learn safely
- Practice weekly

1. Plan your week
2. Complete the project

> Stay coached and keep moving.

```php
echo "hello";
```

| Week | Focus |
| --- | --- |
| 1 | Foundations |

![Course diagram](https://example.com/course-diagram.png)

[Dangerous link](javascript:alert('bad-link'))

<script>alert('unsafe')</script>
<iframe src="https://example.com/embed"></iframe>
<object data="https://example.com/file"></object>
<embed src="https://example.com/file">
<img src="x" onerror="alert('bad-img')">
MARKDOWN,
            'learning_outcomes' => ['Understand foundations', 'Build a project'],
        ]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee('<div class="mk-rich-content">', false)
            ->assertSee('<h1>Course Overview</h1>', false)
            ->assertSee('<li>Learn safely</li>', false)
            ->assertSee('<ol>', false)
            ->assertSee('<blockquote>', false)
            ->assertSee('<code class="language-php">', false)
            ->assertSee('mk-rich-table', false)
            ->assertSee('<table>', false)
            ->assertSee('href="https://example.com"', false)
            ->assertSee('src="https://example.com/course-diagram.png"', false)
            ->assertDontSee('<script>alert', false)
            ->assertDontSee('<iframe', false)
            ->assertDontSee('<object', false)
            ->assertDontSee('<embed', false)
            ->assertDontSee('onerror', false)
            ->assertDontSee('javascript:', false)
            ->assertDontSee('bad-link', false)
            ->assertDontSee('bad-img', false)
            ->assertDontSee('alert(&#039;unsafe&#039;)', false)
            ->assertDontSee("alert('unsafe')", false);
    }

    public function test_course_cards_do_not_render_full_description_rich_content(): void
    {
        $academy = Academy::factory()->create([
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        Course::factory()->create([
            'academy_id' => $academy->id,
            'title' => 'Safe Card Course',
            'slug' => 'safe-card-course',
            'short_description' => 'A normal escaped course summary.',
            'full_description' => '# Private Rich Heading',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $this->get(route('courses'))
            ->assertOk()
            ->assertSee('A normal escaped course summary.')
            ->assertDontSee('<h1>Private Rich Heading</h1>', false)
            ->assertDontSee('mk-rich-content', false);
    }

    public function test_course_detail_handles_missing_overview_and_cover_image(): void
    {
        $academy = Academy::factory()->create([
            'name' => 'Language Academy',
            'status' => Academy::STATUS_PUBLISHED,
        ]);

        $course = Course::factory()->create([
            'academy_id' => $academy->id,
            'title' => 'Fallback Overview Course',
            'slug' => 'fallback-overview-course',
            'short_description' => 'A fallback summary for the public page.',
            'full_description' => null,
            'featured_image_path' => null,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee('A fallback summary for the public page.')
            ->assertSee('images.unsplash.com', false);
    }
}