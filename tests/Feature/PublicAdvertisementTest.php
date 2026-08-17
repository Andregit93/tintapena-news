<?php

namespace Tests\Feature;

use App\Models\Advertisement;
use App\Enums\AdvertisementType;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicAdvertisementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_active_current_image_ad_renders_on_correct_public_placement()
    {
        $media = Media::factory()->create(['disk' => 'public', 'path' => 'test.jpg']);
        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-slot="homepage_top"', false);
        $response->assertSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_image_src_comes_from_related_media_laravel_filesystem_url()
    {
        $media = Media::factory()->create(['disk' => 'public', 'path' => 'test.jpg']);
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
        ]);

        $response = $this->get(route('home'));
        $expectedUrl = \Illuminate\Support\Facades\Storage::disk('public')->url('test.jpg');
        $response->assertSee('src="' . $expectedUrl . '"', false);
    }

    public function test_media_alt_text_is_used_when_available()
    {
        $media = Media::factory()->create(['alt_text' => 'Custom Alt Text']);
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('alt="Custom Alt Text"', false);
    }

    public function test_advertisement_name_is_fallback_alt_when_media_alt_text_blank()
    {
        $media = Media::factory()->create(['alt_text' => null]);
        Advertisement::factory()->create([
            'name' => 'Ad Name Fallback',
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('alt="Ad Name Fallback"', false);
    }

    public function test_valid_https_target_url_produces_clickable_anchor()
    {
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'target_url' => 'https://example.com',
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('<a href="https://example.com"', false);
    }

    public function test_valid_http_target_url_produces_clickable_anchor()
    {
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'target_url' => 'http://example.com',
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('<a href="http://example.com"', false);
    }

    public function test_target_url_null_renders_image_without_clickable_anchor()
    {
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'target_url' => null,
        ]);

        $response = $this->get(route('home'));
        // Make sure it doesn't render an empty href or target_url
        $response->assertDontSee('href=""', false);
    }

    public function test_target_link_includes_security_attributes()
    {
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'target_url' => 'https://example.com',
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer sponsored nofollow"', false);
    }

    public function test_direct_legacy_unsafe_target_url_does_not_become_href()
    {
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'target_url' => 'javascript:alert(1)',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('href="javascript:alert(1)"', false);
    }

    public function test_ftp_url_does_not_become_href()
    {
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'target_url' => 'ftp://example.com',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('href="ftp://example.com"', false);
    }

    public function test_image_ad_with_missing_media_renders_nothing_safely()
    {
        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => null,
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-id="' . $ad->id . '"', false);
    }

    public function test_if_missing_media_image_is_only_eligible_ad_slot_wrapper_is_not_rendered()
    {
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => null,
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
    }

    public function test_active_current_script_ad_renders_its_provider_snippet_only_on_correct_public_placement()
    {
        $ad = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Script,
            'content' => '<script src="https://ads.example.test/provider.js"></script>',
        ]);

        $response = $this->get(route('home'));
        $response->assertSee('data-ad-slot="homepage_top"', false);
        $response->assertSee('<script src="https://ads.example.test/provider.js"></script>', false);
    }

    public function test_blank_script_content_renders_nothing_safely()
    {
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Script,
            'content' => '   ',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
    }

    public function test_inactive_ad_does_not_render()
    {
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => false,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
    }

    public function test_future_ad_does_not_render()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'starts_at' => '2023-01-01 13:00:00',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
    }

    public function test_expired_ad_does_not_render()
    {
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
            'ends_at' => '2023-01-01 11:00:00',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="homepage_top"', false);
    }

    public function test_placement_isolation_remains_correct()
    {
        $media = Media::factory()->create();
        Advertisement::factory()->create([
            'placement_key' => 'article_inline',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media->id,
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('data-ad-slot="article_inline"', false);
    }

    public function test_multiple_renderable_ads_preserve_resolver_ordering()
    {
        $media1 = Media::factory()->create();
        $media2 = Media::factory()->create();

        $ad1 = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media1->id,
            'sort_order' => 10,
        ]);
        $ad2 = Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Image,
            'media_id' => $media2->id,
            'sort_order' => 5,
        ]);

        $response = $this->get(route('home'));

        $html = $response->getContent();
        $pos1 = strpos($html, 'data-ad-id="' . $ad1->id . '"');
        $pos2 = strpos($html, 'data-ad-id="' . $ad2->id . '"');

        $this->assertNotFalse($pos1, 'Ad 1 should be present in the response');
        $this->assertNotFalse($pos2, 'Ad 2 should be present in the response');
        $this->assertTrue($pos2 < $pos1, 'Ad 2 should appear before Ad 1 due to sort_order');
    }

    public function test_script_ad_target_url_must_not_cause_wrapper_anchor()
    {
        Advertisement::factory()->create([
            'placement_key' => 'homepage_top',
            'is_active' => true,
            'type' => AdvertisementType::Script,
            'content' => '<script src="provider.js"></script>',
            'target_url' => 'https://example.com',
        ]);

        $response = $this->get(route('home'));
        $response->assertDontSee('<a href="https://example.com"', false);
    }

    public function test_malicious_script_must_still_not_appear_raw_inside_admin_edit_response()
    {
        $admin = \App\Models\User::factory()->create();
        $ad = Advertisement::factory()->create([
            'type' => AdvertisementType::Script,
            'content' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($admin)->get(\App\Filament\Resources\Advertisements\Pages\EditAdvertisement::getUrl(['record' => $ad]));
        // Filament escapes HTML in Textarea component values by default or uses wire:model
        $response->assertDontSee('<script>alert("xss")</script>', false);
    }
    public function test_advertisement_script_raw_rendering_does_not_weaken_article_rich_text_sanitization()
    {
        // 1. Create a script ad that will be rendered raw
        Advertisement::factory()->create([
            'placement_key' => 'article_inline',
            'is_active' => true,
            'type' => AdvertisementType::Script,
            'content' => '<script>console.log("ad-is-safe")</script>',
        ]);

        // 2. Create an article with malicious content (exactly mirroring PublicArticleTest)
        $article = \App\Models\Article::factory()->create([
            'status' => \App\Enums\ArticleStatus::Published,
            'published_at' => now()->subDay(),
            'content' => '<p>Safe paragraph</p><strong>Safe bold</strong><script>alert(1)</script><img src=x onerror="alert(1)"><a href="javascript:alert(1)">bad link</a>',
        ]);

        $response = $this->get(route('articles.show', $article));
        $response->assertStatus(200);

        // 3. Prove advertisement script is rendered
        $response->assertSee('<script>console.log("ad-is-safe")</script>', false);

        // 4. Prove article content is still sanitized
        $response->assertSee('<p>Safe paragraph</p>', false);
        $response->assertSee('<strong>Safe bold</strong>', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('onerror=', false);
        $response->assertDontSee('javascript:', false);
    }
}
