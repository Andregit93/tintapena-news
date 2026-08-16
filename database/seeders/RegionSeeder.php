<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            ['name' => 'Pangkalpinang', 'slug' => 'pangkalpinang'],
            ['name' => 'Bangka', 'slug' => 'bangka'],
            ['name' => 'Bangka Barat', 'slug' => 'bangka-barat'],
            ['name' => 'Bangka Tengah', 'slug' => 'bangka-tengah'],
            ['name' => 'Bangka Selatan', 'slug' => 'bangka-selatan'],
            ['name' => 'Belitung', 'slug' => 'belitung'],
            ['name' => 'Belitung Timur', 'slug' => 'belitung-timur'],
        ];

        foreach ($regions as $region) {
            Region::updateOrCreate(
                ['slug' => $region['slug']],
                [
                    'name' => $region['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
