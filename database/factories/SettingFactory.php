<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_name' => 'general',
            'setting_key' => $this->faker->unique()->word() . '_setting',
            'value' => $this->faker->word(),
            'value_type' => 'string',
        ];
    }
}
