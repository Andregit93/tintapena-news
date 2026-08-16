<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Politik', 'slug' => 'politik'],
            ['name' => 'Pemerintahan', 'slug' => 'pemerintahan'],
            ['name' => 'Ekonomi', 'slug' => 'ekonomi'],
            ['name' => 'Hukum & Kriminal', 'slug' => 'hukum-kriminal'],
            ['name' => 'Pendidikan', 'slug' => 'pendidikan'],
            ['name' => 'Kesehatan', 'slug' => 'kesehatan'],
            ['name' => 'Pariwisata', 'slug' => 'pariwisata'],
            ['name' => 'Olahraga', 'slug' => 'olahraga'],
            ['name' => 'Opini', 'slug' => 'opini'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
