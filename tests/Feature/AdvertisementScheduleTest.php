<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Enums\AdvertisementType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Media;
use Livewire\Livewire;
use App\Filament\Resources\Advertisements\Pages\EditAdvertisement;

class AdvertisementScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_active_advertisement_with_null_dates_is_visible()
    {
        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_inactive_advertisement_is_hidden()
    {
        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => false,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_starts_at_in_future_is_hidden()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'starts_at' => '2023-01-01 13:00:00',
            'ends_at' => null,
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_starts_at_exactly_now_is_visible()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'starts_at' => '2023-01-01 12:00:00',
            'ends_at' => null,
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_starts_at_in_past_with_null_ends_at_is_visible()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'starts_at' => '2023-01-01 11:00:00',
            'ends_at' => null,
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_ends_at_in_future_with_null_starts_at_is_visible()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => '2023-01-01 13:00:00',
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_ends_at_exactly_now_is_hidden()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => '2023-01-01 12:00:00',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_ends_at_in_past_is_hidden()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => '2023-01-01 11:00:00',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_advertisement_inside_complete_valid_window_is_visible()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'starts_at' => '2023-01-01 11:00:00',
            'ends_at' => '2023-01-01 13:00:00',
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_inactive_advertisement_stays_hidden_even_inside_valid_schedule()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => false,
            'starts_at' => '2023-01-01 11:00:00',
            'ends_at' => '2023-01-01 13:00:00',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_inactive_to_active_through_admin_edit_becomes_eligible()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));
        $admin = User::factory()->create();

        $media = Media::factory()->create();

        $ad = Advertisement::factory()->create([
            'name' => 'Test Ad',
            'placement_key' => 'homepage_top',
            'is_active' => false,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'starts_at' => '2023-01-01 11:00:00',
            'ends_at' => '2023-01-01 13:00:00',
        ]);

        $this->get(route('home'))->assertDontSee('data-ad-id="' . $ad->id . '"', false);

        Livewire::actingAs($admin)
            ->test(EditAdvertisement::class, ['record' => $ad->id])
            ->fillForm([
                'is_active' => true,
                'media_id' => $media->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $ad->refresh();
        $this->assertTrue($ad->is_active);

        $this->get(route('home'))->assertSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_active_to_inactive_through_admin_edit_becomes_hidden()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));
        $admin = User::factory()->create();

        $media = Media::factory()->create();

        $ad = Advertisement::factory()->create([
            'name' => 'Test Ad 2',
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'starts_at' => '2023-01-01 11:00:00',
            'ends_at' => '2023-01-01 13:00:00',
        ]);

        $this->get(route('home'))->assertSee('data-ad-id="' . $ad->id . '"', false);

        Livewire::actingAs($admin)
            ->test(EditAdvertisement::class, ['record' => $ad->id])
            ->fillForm([
                'is_active' => false,
                'media_id' => $media->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $ad->refresh();
        $this->assertFalse($ad->is_active);

        $this->get(route('home'))->assertDontSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_expired_future_advertisement_does_not_create_slot_marker()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'ends_at' => '2023-01-01 11:00:00', // expired
        ]);

        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'starts_at' => '2023-01-01 13:00:00', // future
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
    }

    public function test_hidden_advertisement_does_not_affect_ordering_of_visible_ads()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        $ad1 = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $ad2 = Advertisement::factory()->create([ // hidden
            'placement_key' => 'homepage_top',
            'is_active' => false,
            'sort_order' => 1,
        ]);
        $ad3 = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $action = new \App\Actions\Advertisements\GetAdvertisementsForPlacement();
        $ads = $action->execute('homepage_top');

        $this->assertCount(2, $ads);
        $this->assertEquals($ad3->id, $ads[0]->id); // sort_order 5
        $this->assertEquals($ad1->id, $ads[1]->id); // sort_order 10
    }

    public function test_existing_placement_isolation_remains_correct_with_schedule()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));

        Advertisement::factory()->create([
            'placement_key' => 'article_inline',
            'is_active' => true,
            'starts_at' => '2023-01-01 11:00:00',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="article_inline"', false);
    }

    public function test_malicious_script_content_is_not_emitted_raw_with_schedule()
    {
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Script,
            'content' => '<script>alert("ads-placement-xss")</script>',
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-slot="homepage_top"', false);
        $response->assertDontSee('<script>alert("ads-placement-xss")</script>', false);
    }
}
