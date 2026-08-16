<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArticleViewStat>
 */
class ArticleViewStatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'period_start' => now()->startOfHour(),
            'views_count' => $this->faker->numberBetween(1, 100),
        ];
    }
}
