<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence();
        return [
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
            // region_id, featured_media_id can be null or overridden by states/factory methods
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->paragraph(),
            'content' => $this->faker->paragraphs(3, true),
            'status' => ArticleStatus::Draft,
            'views_count' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Draft,
            'scheduled_at' => null,
            'published_at' => null,
            'archived_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Scheduled,
            'scheduled_at' => now()->addDays(2),
            'published_at' => null,
            'archived_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDays(1),
            'scheduled_at' => null,
            'archived_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Archived,
            'published_at' => now()->subDays(2),
            'archived_at' => now(),
            'scheduled_at' => null,
        ]);
    }
}
