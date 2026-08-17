<?php

namespace Database\Seeders;

use App\Models\HomepageSlot;
use Illuminate\Database\Seeder;

class HomepageSlotSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['slot_key' => 'headline_main', 'slot_name' => 'Headline Utama', 'sort_order' => 10],
            ['slot_key' => 'headline_2', 'slot_name' => 'Headline Pendukung 1', 'sort_order' => 20],
            ['slot_key' => 'headline_3', 'slot_name' => 'Headline Pendukung 2', 'sort_order' => 30],
            ['slot_key' => 'editor_pick_1', 'slot_name' => 'Pilihan Redaksi 1', 'sort_order' => 40],
            ['slot_key' => 'editor_pick_2', 'slot_name' => 'Pilihan Redaksi 2', 'sort_order' => 50],
            ['slot_key' => 'editor_pick_3', 'slot_name' => 'Pilihan Redaksi 3', 'sort_order' => 60],
            ['slot_key' => 'editor_pick_4', 'slot_name' => 'Pilihan Redaksi 4', 'sort_order' => 70],
        ];

        foreach ($slots as $slot) {
            HomepageSlot::updateOrCreate(
                ['slot_key' => $slot['slot_key']],
                [
                    'slot_name' => $slot['slot_name'],
                    'sort_order' => $slot['sort_order'],
                ]
            );
        }
    }
}
