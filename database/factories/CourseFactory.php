<?php

namespace Database\Factories;

use App\Models\Academy;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'academy_id' => Academy::factory(),
            'title' => str($title)->headline()->toString(),
            'slug' => Str::slug($title),
            'short_description' => fake()->sentence(14),
            'full_description' => fake()->paragraphs(3, true),
            'level' => fake()->randomElement(['Beginner', 'Intermediate', 'Advanced']),
            'duration' => fake()->randomElement(['4 weeks', '6 weeks', '8 weeks', '10 weeks']),
            'price' => fake()->randomFloat(2, 25, 250),
            'status' => fake()->randomElement(Course::STATUSES),
            'featured_image_path' => null,
            'learning_outcomes' => [
                fake()->sentence(5),
                fake()->sentence(5),
                fake()->sentence(5),
            ],
        ];
    }
}
