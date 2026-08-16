<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BreakingNews>
 */
class BreakingNewsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => null,
            'created_by' => null,
            'headline' => $this->faker->sentence(),
            'target_url' => $this->faker->url(),
            'starts_at' => now(),
            'ends_at' => now()->addHours(24),
            'is_active' => true,
        ];
    }
}
