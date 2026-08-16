<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HomepageSlot>
 */
class HomepageSlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slot_key' => $this->faker->unique()->word() . '_slot',
            'slot_name' => $this->faker->words(3, true),
            'article_id' => null,
            'updated_by' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
