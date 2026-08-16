<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uploaded_by' => User::factory(),
            'disk' => 'public',
            'path' => 'media/' . $this->faker->uuid() . '.jpg',
            'filename' => $this->faker->uuid() . '.jpg',
            'original_filename' => 'image.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => $this->faker->numberBetween(10000, 500000),
            'width' => 800,
            'height' => 600,
            'alt_text' => $this->faker->sentence(3),
            'caption' => $this->faker->sentence(),
            'photo_credit' => $this->faker->name(),
        ];
    }
}
