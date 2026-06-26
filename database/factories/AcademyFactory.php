<?php

namespace Database\Factories;

use App\Models\Academy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Academy>
 */
class AcademyFactory extends Factory
{
    protected $model = Academy::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true).' Academy';

        return [
            'name' => str($name)->title()->toString(),
            'slug' => Str::slug($name),
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraphs(2, true),
            'icon' => fake()->randomElement(['code', 'language', 'target', 'briefcase', 'graduation-cap']),
            'status' => fake()->randomElement(Academy::STATUSES),
        ];
    }
}
