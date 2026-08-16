<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence();
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => $this->faker->paragraphs(3, true),
            'status' => PageStatus::Published,
            'seo_title' => null,
            'meta_description' => null,
            'published_at' => now(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
