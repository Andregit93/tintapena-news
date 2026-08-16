<?php

namespace Database\Factories;

use App\Enums\AdvertisementType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Advertisement>
 */
class AdvertisementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'type' => AdvertisementType::Image,
            'placement_key' => 'homepage_top',
            'media_id' => null,
            'content' => null,
            'target_url' => $this->faker->url(),
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
